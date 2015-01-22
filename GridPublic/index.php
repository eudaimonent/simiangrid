<?php
/**
 * Simian grid services
 *
 * PHP version 5
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    SimianGrid
 * @author     John Hurliman <http://software.intel.com/en-us/blogs/author/john-hurliman/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */

$gStartTime = microtime(true);
$gMethodName = "(Unknown)";

define('COMMONPATH', str_replace("\\", "/", realpath(dirname(__FILE__) . '/..') . '/GridCommon/'));
define('BASEPATH', str_replace("\\", "/", realpath(dirname(__FILE__)) . '/'));
define('URLBASE', str_replace($_SERVER['DOCUMENT_ROOT'],"",BASEPATH));


require_once(COMMONPATH . 'Config.php');
require_once(COMMONPATH . 'Errors.php');
require_once(COMMONPATH . 'Log.php');
require_once(COMMONPATH . 'Interfaces.php');
require_once(COMMONPATH . 'UUID.php');
require_once(COMMONPATH . 'Vector3.php');
require_once(COMMONPATH . 'Curl.php');
require_once(COMMONPATH . 'Capability.php');
require_once(COMMONPATH . 'SimianGrid.php');

require_once(COMMONPATH . 'llsd/llsd_classes.php');
require_once(COMMONPATH . 'llsd/llsd_encode.php');
require_once(COMMONPATH . 'llsd/llsd_decode.php');

// -----------------------------------------------------------------
// Performance profiling/logging
// -----------------------------------------------------------------
function shutdown()
{
    global $gStartTime, $gMethodName, $gDetailedLogging;
    $elapsed = microtime(true) - $gStartTime;

    if ($gDetailedLogging >= 5)
    {
        $obsize = ob_get_length();
        ob_end_flush();
        log_message('debug', "Executed $gMethodName in $elapsed seconds, $obsize bytes");
    }
    else
    {
        log_message('debug', "Executed $gMethodName in $elapsed seconds");
    }
}

register_shutdown_function('shutdown');

// -----------------------------------------------------------------
// Common error response
function RequestFailed($msg)
{
    header("HTTP/1.1 400 Bad Request");
    echo $msg;
    exit();
}


// -----------------------------------------------------------------

// -----------------------------------------------------------------
// Configuration loading
// -----------------------------------------------------------------
$config =& get_config();

$gDetailedLogging = $config['log_threshold'];
if ($gDetailedLogging >= 5)
{
    //log_message('debug', "Request: " . json_encode($_REQUEST));
    //log_message('debug', "Headers: " . json_encode(apache_request_headers()));

    ob_start();
}

// -----------------------------------------------------------------
// Disable magic quotes
// -----------------------------------------------------------------
if (get_magic_quotes_gpc())
{
    log_message('debug', "Magic quotes detected, disabling");
    
    function stripslashes_gpc(&$value) { $value = stripslashes($value); }
    
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

//log_message('debug', 'script name is ' . $_SERVER["PHP_SELF"]);
//log_message('debug', 'full uri is ' . $_SERVER["REQUEST_URI"]);

// -----------------------------------------------------------------
// Extract the capability and operation from the request
// -----------------------------------------------------------------
$capability = null;
$operation = null;
$request = null;

$cappattern='@^' . URLBASE . 'CAP/([^/?]+)/([^/]+)/?(\?.*)?$@';
$nocappattern='@^' . URLBASE . '([^/?]+)/?(\?.*)?$@';

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
    $requestURI = preg_replace('@/+@','/',$_SERVER["REQUEST_URI"]);
    if (preg_match($cappattern,$requestURI,$capmatches))
    {
        $capability = $capmatches[1];
        $operation = $capmatches[2];
        $request = $_REQUEST;
    }
    else if (preg_match($nocappattern,$requestURI,$capmatches))
    {
        $operation = $capmatches[1];
        $request = $_REQUEST;
    }
    else
    {
        log_message('warn', 'Invalid request: ' . $requestURI);
        RequestFailed('Invalid request format');
    }
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    // Grid service call
    if (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded')
    {
        $request = $_REQUEST;
        $capability = trim($request['cap']);
        $operation = trim($request['RequestMethod']);
    }
    else if ($_SERVER['CONTENT_TYPE'] == 'application/json')
    {
        $data = file_get_contents("php://input");
        $json = json_decode($data, true);
        
        if ($json)
        {
            $request = $json;
            $capability = trim($request['cap']);
            $operation = trim($request['RequestMethod']);
        }
        else
        {
            log_message('warn', "Error decoding JSON request");
            log_message('debug', "Invalid JSON request data: " . $data);
            
            RequestFailed('Error decoding JSON request');
        }
    }
    else if ($_SERVER['CONTENT_TYPE'] == 'application/llsd+xml')
    {
        $requestURI = preg_replace('@/+@','/',$_SERVER["REQUEST_URI"]);
        if (preg_match($cappattern,$requestURI,$capmatches))
        {
            $capability = $capmatches[1];
            $operation = $capmatches[2];
        }
        else if (preg_match($nocappattern,$requestURI,$capmatches))
        {
            $operation = $capmatches[1];
            $request = $_REQUEST;
        }
        else
        {
            log_message('warn', 'Invalid request: ' . $requestURI);

            RequestFailed(sprintf('Invalid llsd request format; %s in %s',$requestURI,$xp));
        }

        $data = file_get_contents("php://input");
        $llsd = llsd_decode($data);
        if ($llsd)
        {
            $request = $llsd;
        }
        else
        {
            log_message('warn', "Error decoding LLSD request");
            RequestFailed('Error decoding LLSD request');
        }
    }
    else
    {
        log_message('warn', "unknown post request type " . $_SERVER['CONTENT_TYPE']);
        RequestFailed('Error decoding request');
    }
}

log_message('debug',sprintf("cap=%s, op=%s, request=%s",$capability,$operation,json_encode($request)));

// --------------- validate the capability ---------------
if (!empty($config['authorize_commands']))
{
    if (! UUID::TryParse($capability,$capid))
    {
        log_message('warn',sprintf("invalid uuid %s", $capability));
        RequestFailed('Invalid capability');
    }

    $cap = get_capability($capability);
    if ($cap == null)
    {
        log_message('warn',sprintf("invalid capability %s",$capability));
        RequestFailed('Invalid capability');
    }

    // log_message('debug',sprintf("Capability=%s",json_encode($cap)));
}

// execute_command($operation, $capability, $request);
if (file_exists(BASEPATH . "lib/Class.$operation.php"))
{
    if (include_once(BASEPATH . "lib/Class.$operation.php"))
    {
        $gMethodName = $operation;
        $instance = new $operation();
        $instance->Execute($request);
        exit();
    }
}

log_message('warn',sprintf("unknown operation %s",$operation));
RequestFailed('No such operation');

