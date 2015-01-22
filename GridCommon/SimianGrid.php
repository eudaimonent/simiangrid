<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * @author     Mic Bowman
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */

require_once(COMMONPATH . 'Config.php');
require_once(COMMONPATH . 'Errors.php');
require_once(COMMONPATH . 'Log.php');
require_once(COMMONPATH . 'Interfaces.php');
require_once(COMMONPATH . 'UUID.php');
require_once(COMMONPATH . 'Vector3.php');
require_once(COMMONPATH . 'Curl.php');

/////////////////////////////////////////////////////////////////
// Utility Functions
/////////////////////////////////////////////////////////////////
function ends_with($str, $sub)
{
    return (substr($str, strlen($str) - strlen($sub)) == $sub);
}

function decode_recursive_json($json)
{
    if ( is_string($json) ) {
        $response = json_decode($json, TRUE);
        if ( $response === null || ! is_array($response) ) {
            return $json;
        }
    } else if ( is_array($json) ) {
        $response = $json;
    } else {
        return $json;
    }
    if ( $response == null ) {
        return $json;
    }
    foreach ( $response as $key => $value ) {
        $response[$key] = decode_recursive_json($value);
    }
    return $response;
}

function webservice_post($url, $params, $jsonRequest = FALSE)
{
    // Parse the RequestMethod out of the request for debugging purposes
    if (isset($params['RequestMethod']))
        $requestMethod = $params['RequestMethod'];
    else
        $requestMethod = '';

    if (empty($url))
    {
        log_message('error', "Canceling $requestMethod POST to an empty URL");
        return array('Message' => 'Web service URL is not configured');
    }

    $options = array();
    if ($jsonRequest)
    {
        $params = json_encode($params);
	$options[CURLOPT_HTTPHEADER] = array('Content-Type: application/json');
    }

    // POST our query and fetch the response
    $curl = new Curl();
    $response = $curl->simple_post($url, $params, $options);

    // JSON decode the response
    $response = decode_recursive_json($response);

    if (!isset($response))
        $response = array('Message' => 'Invalid or missing response');

    return $response;
}

/////////////////////////////////////////////////////////////////
// Capability Functions
/////////////////////////////////////////////////////////////////
function get_capability($capID)
{
    $config =& get_config();
    $userService = $config['user_service'];

    $response = webservice_post($userService, array(
        'RequestMethod' => 'GetCapability',
        'CapabilityID' => $capID)
    );

    if (!empty($response['Success']) && $response['Resource'] == 'login')
        return $response['OwnerID'];

    return null;
}

/////////////////////////////////////////////////////////////////
// User Functions
/////////////////////////////////////////////////////////////////
function authorize_identity($name, $passHash)
{
    $config =& get_config();
    $userService = $config['user_service'];

    log_message('warn',"authorize $name with $passHash to $userService");

    $userID = NULL;

    $response = webservice_post($userService, array(
        'RequestMethod' => 'AuthorizeIdentity',
        'Identifier' => $name,
        'Credential' => $passHash,
        'Type' => 'md5hash')
    );

    if (!empty($response['Success']))
        UUID::TryParse($response['UserID'], $userID);

    return $userID;
}

function get_user_by_id($userID)
{
    log_message('info',"get user: $userID");

    $config =& get_config();
    $userService = $config['user_service'];

    $response = webservice_post($userService, array(
        'RequestMethod' => 'GetUser',
        'UserID' => $userID)
    );

    if (!empty($response['Success']) && !empty($response['User']))
        return $response['User'];

    return null;
}

function get_user_by_name($userName)
{
    log_message('info',"get user: $userName");

    $config =& get_config();
    $userService = $config['user_service'];

    $response = webservice_post($userService, array(
        'RequestMethod' => 'GetUser',
        'Name' => $userName)
    );

    if (!empty($response['Success']) && !empty($response['User']))
        return $response['User'];

    return null;
}

function create_user($userID, $userName, $email)
{
    log_message('info',"create user: $userID, $userName");

    $config =& get_config();
    $userService = $config['user_service'];

    $query = array(
        'RequestMethod' => 'AddUser',
        'UserID' => $userID,
        'Name' => $userName,
        'Email' => $email,
        'AccessLevel' => 0);

    $response = webservice_post($userService, $query);

    if (isset($response['Success']))
        return $response['Success'];

    return false;
}

function set_user_data($userID, $key, $value)
{
    log_message('info',"set user data: $userID <$key, $value>");

    $config =& get_config();
    $userService = $config['user_service'];

    $query = array('RequestMethod' => 'AddUserData',
                   'UserID' => $userID,
                   $key => $value);

    $response = webservice_post($userService, $query);

    if (isset($response['Success']))
        return $response['Success'];

    return false;
}

/////////////////////////////////////////////////////////////////
// Session Functions
/////////////////////////////////////////////////////////////////
function get_session($userID)
{
    log_message('info',"get session: $userID");

    $config =& get_config();
    $userService = $config['user_service'];

    $response = webservice_post($userService, array(
        'RequestMethod' => 'GetSession',
        'UserID' => $userID)
    );

    if (!empty($response['Success']))
        return $response;

    return null;
}

function add_session($userID, &$sessionID, &$secureSessionID, $extradata = null)
{
    log_message('info',"add session: $userID");

    $config =& get_config();
    $userService = $config['user_service'];

    $request = array(
        'RequestMethod' => 'AddSession',
        'UserID' => $userID);
    
    if ($extradata != null)
    {
        $xd = json_encode($extradata);
        $request['ExtraData'] = $xd;
    }

    $response = webservice_post($userService, $request);

    if (!empty($response['Success']) &&
        UUID::TryParse($response['SessionID'], $sessionID) &&
        UUID::TryParse($response['SecureSessionID'], $secureSessionID))
    {
        return true;
    }

    return false;
}

function remove_session($sessionID)
{
    log_message('info',"remove session: $sessionID");

    $config =& get_config();
    $userService = $config['user_service'];

    $response = webservice_post($userService, array(
        'RequestMethod' => 'RemoveSession',
        'SessionID' => $sessionID)
    );

    if (!empty($response['Success']))
        return true;

    return false;
}

function create_session($userID, $sessionID, $secureSessionID, $extradata = null)
{
    log_message('info',"create session: $userID");

    $config =& get_config();
    $gridService = $config['grid_service'];

    $request = array(
        'RequestMethod' => 'AddSession',
        'UserID' => $userID,
        'SessionID' => $sessionID,
        'SecureSessionID' => $secureSessionID);
    
    if ($extradata != null)
    {
        $xd = json_encode($extradata);
        $request['ExtraData'] = $xd;
    }

    $response = webservice_post($gridService, $request);

    if (!empty($response['success'])) {
        return true;
    } else {
        return false;
    }
}

/////////////////////////////////////////////////////////////////
// Scene Functions
/////////////////////////////////////////////////////////////////
function lookup_scene_by_id($sceneID)
{
    log_message('info',"lookup scene by id: $sceneID");

    $config =& get_config();
    $gridService = $config['grid_service'];

    $response = webservice_post($gridService, array(
        'RequestMethod' => 'GetScene',
        'SceneID' => $sceneID,
        'Enabled' => '1')
    );

    if (!empty($response['Success']))
        return Scene::fromOSD($response);

    return null;
}

function lookup_scene_by_name($name)
{
    log_message('info',"lookup scene by name: $name");

    $config =& get_config();
    $gridService = $config['grid_service'];

    $response = webservice_post($gridService, array(
        'RequestMethod' => 'GetScene',
        'Name' => $name,
        'Enabled' => '1')
    );

    if (!empty($response['Success']))
        return Scene::fromOSD($response);

    return null;
}

function lookup_scene_by_position($position, $findClosest = false)
{
    log_message('info',"lookup scene by position: " . $position->toOSD());

    $config =& get_config();
    $gridService = $config['grid_service'];

    $response = webservice_post($gridService, array(
        'RequestMethod' => 'GetScene',
        'Position' => $position,
        'FindClosest' => ($findClosest ? '1' : '0'),
        'Enabled' => '1')
    );

    if (!empty($response['Success']))
        return Scene::fromOSD($response);

    return null;
}

/////////////////////////////////////////////////////////////////
// Map Tile Functions
/////////////////////////////////////////////////////////////////
function add_map_tile($x, $y, $maptile)
{
    $params = array(
        'X' => $x,
        'Y' => $y
    );
    $config =& get_config();
    $gridService = $config['grid_service'];
    $curl = new Curl($gridService);
    $result = $curl->multipart('Tile', 'image/jpeg', $maptile, $params);
    var_dump($result);
    return $result['Success'];
}

function refresh_map_tile($x, $y, $regionImage)
{
    $success = false;
    $curl = new Curl();
    $maptile = $curl->simple_get($regionImage);
    if ( $maptile ) {
        if ( ! add_map_tile($x, $y, $maptile) ) {
            log_message('warn', "Unable to upload map image from $regionImage");
        } else {
            $success = true;
        }
    } else {
        log_message('warn', "unable to fetch map image from $regionImage");
    }
    return $success;
}

/////////////////////////////////////////////////////////////////
// Asset Functions
/////////////////////////////////////////////////////////////////
function get_asset($assetID, &$assetData)
{
    log_message('info',"get_asset: " . $assetID);

    // We use the grid service here because we don't want the
    // asset specific handling that comes from the asset service
    $config =& get_config();
    $service = $config['grid_service'];

    $response = webservice_post($service, array(
        'RequestMethod' => 'xGetAsset',
        'ID' => $assetID));

    if (! empty($response['Success']))
    {
        $assetData = array();
        $assetData['SHA256'] = $response['SHA256'];
        $assetData['Last-Modified'] = $response['Last-Modified'];
        $assetData['CreatorID'] = $response['CreatorID'];
        $assetData['ContentType'] = $response['ContentType'];
        $assetData['Content'] = base64_decode($response['EncodedData']);

        return true;
    }

    $assetData = null;
    return false;
}

function get_assetmetadata($assetID, &$assetData)
{
    log_message('info',"get_assetmetadata: " . $assetID);

    // We use the grid service here because we don't want the
    // asset specific handling that comes from the asset service
    $config =& get_config();
    $service = $config['grid_service'];

    $response = webservice_post($service, array(
        'RequestMethod' => 'xGetAssetMetadata',
        'ID' => $assetID));

    if (! empty($response['Success']))
    {
        $assetData = array();
        $assetData['SHA256'] = $response['SHA256'];
        $assetData['Last-Modified'] = $response['Last-Modified'];
        $assetData['CreatorID'] = $response['CreatorID'];
        $assetData['ContentType'] = $response['ContentType'];

        return true;
    }

    $assetData = null;
    return false;
}

/////////////////////////////////////////////////////////////////
// Inventory Functions
/////////////////////////////////////////////////////////////////
function get_inventory_items($userID, $folderID, &$items, $childrenOnly = 0, $includeFolders = 1, $includeItems = 0)
{
    $config =& get_config();
    $inventoryService = $config['inventory_service'];

    $response = webservice_post($inventoryService, array(
        'RequestMethod' => 'GetInventoryNode',
        'ItemID' => $folderID,
        'OwnerID' => $userID,
        'IncludeFolders' => $includeFolders,
        'IncludeItems' => $includeItems,
        'ChildrenOnly' => $childrenOnly));

    if (! empty($response['Success']) && is_array($response['Items']))
    {
        $items = $response['Items'];
        return true;
    }

    $items = null;
    return false;
}

function get_inventory_skel($userID, &$rootFolderID, &$items)
{
    $config =& get_config();
    $inventoryService = $config['inventory_service'];

    // This is always true in SimianGrid
    $rootFolderID = $userID;

    return(get_inventory_items($userID,$rootFolderID,$items,0,1,0));
}

/////////////////////////////////////////////////////////////////
// Generics Functions
/////////////////////////////////////////////////////////////////
function get_friends($userID)
{
    $config =& get_config();
    $userService = $config['user_service'];

    $friends = array();

    // Load the list of friends and their granted permissions
    $response = webservice_post($userService, array(
        'RequestMethod' => 'GetGenerics',
        'OwnerID' => $userID,
        'Type' => 'Friend')
    );

    if (!empty($response['Success']) && is_array($response['Entries']))
    {
        $friendEntries = $response['Entries'];

        // Populate the friends array
        foreach ($friendEntries as $friendEntry)
        {
            $friendID = $friendEntry['Key'];
            $friends[$friendID] = array('buddy_rights_has' => 0, 'buddy_rights_given' => (int)$friendEntry['Value'], 'buddy_id' => $friendID);
        }

        // Load the permissions those friends have granted to this user
        $response = webservice_post($userService, array(
            'RequestMethod' => 'GetGenerics',
            'Key' => $userID,
            'Type' => 'Friend')
        );

        if (!empty($response['Success']) && is_array($response['Entries']))
        {
            $friendedMeEntries = $response['Entries'];

            foreach ($friendedMeEntries as $friendedMeEntry)
            {
                $friendID = $friendedMeEntry['OwnerID'];

                if (isset($friends[$friendID]))
                {
                    $friends[$friendID]['buddy_rights_has'] = $friendedMeEntry['Value'];
                }
            }
        }
        else
        {
            log_message('warn', "Failed to retrieve the reverse friends list for " . $userID . " from " . $userService . ": " . $response['Message']);
        }
    }
    else
    {
        log_message('warn', "Failed to retrieve the friends list for " . $userID . " from " . $userService . ": " . $response['Message']);
    }

    // Convert the friends associative array into a plain array
    $ret = array();
    foreach ($friends as $friend)
        $ret[] = $friend;

    return $ret;
}

/////////////////////////////////////////////////////////////////
// Simualtor Interaction Functions
/////////////////////////////////////////////////////////////////
function create_opensim_presence($scene, $userID, $circuitCode, $fullName, $appearance,
    $sessionID, $secureSessionID, $startPosition, &$seedCapability)
{
    $config =& get_config();
    $serviceurls = array(
        'GatekeeperURI' => $config['hypergrid_uri'],
        'HomeURI' => $config['hypergrid_uri'],
        'InventoryServerURI' => $config['hg_inventory_service'],
        'AssetServerURI' => $config['hg_asset_service'],
        'ProfileServerURI' => $config['hg_user_service'],
        'FriendsServerURI' => $config['hypergrid_uri'],
        'IMServerURI' => $config['hypergrid_uri']
    );

    $capsPath = UUID::Random();

    return create_opensim_presence_full($scene->Address, $scene->Name, $scene->SceneID, $scene->MinPosition->X, $scene->MinPosition->Y,
                                        $userID, $circuitCode, $fullName, $appearance, $sessionID, $secureSessionID, $startPosition,
                                        $capsPath, null, $serviceurls, 128, null,
                                        $seedCapability);
}



function create_opensim_presence_full($server_uri, $scene_name, $scene_uuid, $scene_x, $scene_y,
                                      $userID, $circuitCode, $fullName, $appearance, $sessionID, $secureSessionID, $startPosition,
                                      $capsPath, $client_ip, $service_urls, $tp_flags, $service_session_id,
                                      &$seedCapability)
{
    log_message('info',"Create OpenSim presence in $server_uri");

    if (!ends_with($server_uri, '/'))
        $server_uri .= '/';
    $regionUrl = $server_uri . 'agent/' . $userID . '/';

    list($firstName, $lastName) = explode(' ', $fullName);

    $request = array(
        'agent_id' => $userID,
        'caps_path' => $capsPath,
        'child' => false,
        'circuit_code' => $circuitCode,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'session_id' => $sessionID,
        'secure_session_id' => $secureSessionID,
        'start_pos' => (string)$startPosition,
        'destination_x' => $scene_x,
        'destination_y' => $scene_y,
        'destination_name' => $scene_name,
        'destination_uuid' => $scene_uuid,
	'packed_appearance' => $appearance,
        'appearance_serial' => 1,
	'teleport_flags' => $tp_flags, // (1 << 30) is HG, 128 is normal
        'child' => true
    );

    if ( $client_ip != null )
    {
        $request['client_ip'] = $client_ip;
    }

    if ( $service_urls != null )
    {
        $request['serviceurls'] = $service_urls;
    }

    if ( $service_session_id != null )
    {
        $request['service_session_id'] = $service_session_id;
    }

    $response = webservice_post($regionUrl, $request, true);
    if (!empty($response['success']))
    {
        // This is the hardcoded format OpenSim uses for seed capability URLs
        $seedCapability = $server_uri . 'CAPS/' . $capsPath . '0000/';
        return $response['success'];
    }

    log_message('warn',"failed to create presence for $userID");

    $seedCapability = null;
    return false;
}

