<?php
/** Simian grid services
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
 * @author     Michael Heilmann
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 */

// These should have been loaded already
require_once(COMMONPATH . 'Config.php');
require_once(COMMONPATH . 'Errors.php');
require_once(COMMONPATH . 'Log.php');
require_once(COMMONPATH . 'Interfaces.php');
require_once(COMMONPATH . 'UUID.php');
require_once(COMMONPATH . 'Vector3.php');
require_once(COMMONPATH . 'Curl.php');
require_once(COMMONPATH . 'Capability.php');
require_once(COMMONPATH . 'SimianGrid.php');

class GetMesh implements IPublicService
{
	private static $MeshTypes = array("application/vnd.ll.mesh" => 1);

	// -----------------------------------------------------------------
    private function ComputeMeshFile($id)
    {
        $config =& get_config();

        $meshdir = BASEPATH . 'meshes';
        if (! empty($config['mesh_path']))
            $meshdir = $config['mesh_path'];

        $splitsize = 2;
        if (! empty($config['mesh_split_size']))
            $splitsize = $config['mesh_split_size'];
        
        $splitdepth = 2;
        if (! empty($config['mesh_split_depth']))
            $splitsize = $config['mesh_split_depth'];
        
        $meshdir = $meshdir . "/";
        for ($i = 0; $i < $splitdepth; $i++)
        {
            $meshdir = $meshdir . substr($id,$i*$splitsize,$splitsize) . '/';
            if (! file_exists($meshdir))
            {
                // the '@' should suppress errors.. we really don't care if it fails so long 
                // as the directory exists, there is definitely a race condition but a fail
                // is ok since this condition can only happen on first access to a texture
                if (! @mkdir($meshdir,0775))
                {
                    // This error happens during a race condition for creating a directory
                    // For the moment, just log it and ignore
                    log_message('error','[GetMesh] Caught exception in mkdir;');
                }
            }
        }

        // And return the path
        return $meshdir . $id;
    }

    // -----------------------------------------------------------------
    private function GetRange($datalen)
    {
        $range = array(0,$datalen-1);
        
        if (! empty($_SERVER['HTTP_RANGE']))
        {
            if (preg_match('/bytes=([0-9]+)-([0-9]+)?/',$_SERVER['HTTP_RANGE'],$rmatches))
            {
                $range[0] = $rmatches[1];
                $range[1] = (empty($rmatches[2]) ? $datalen - 1 : $rmatches[2]);
            }
            else
                log_message('warn','[GetTexture] invalid range specification; ' . $_SERVER['HTTP_RANGE']);
            // log_message('debug',sprintf('[GetTexture] request <%s>, respond <%d,%d>',$_SERVER['HTTP_RANGE'],$range[0],$range[1]));
        }
        else
            log_message('warn','[GetTexture] no range specification provided');

        // should add a few more sanity checks here...
        if ($range[1] >= $datalen)
            $range[1] = $datalen - 1;

        return $range;
    }      

    // -----------------------------------------------------------------
    private function SendMeshFile($id,$file)
    {
        $handle = fopen($file,"rb");
        flock($handle,LOCK_SH); /* readers lock */

        $datalen = filesize($file);
        $datarange = $this->GetRange($datalen);
        $sendlen = $datarange[1] - $datarange[0] + 1;

        // From OpenSim code... we send this anyway
        header("HTTP/1.1 206 Partial Content");
        header('Accept-Ranges: bytes');
        header('Content-Range: bytes '. $datarange[0] . '-' . $datarange[1] . '/' . $datalen);
        header("Content-Type: application/vnd.ll.mesh");
        header("Content-Length: " . $sendlen);

        fseek($handle,$datarange[0]);
        print(fread($handle, $sendlen));

        flock($handle,LOCK_UN);
        fclose($handle);

        log_message('debug',sprintf('[GetMesh] sent range %d-%d/%d from %s',$datarange[0],$datarange[1],$datalen,$id));
        exit();
	}
    
	// -----------------------------------------------------------------
    public function Execute($params)
    {
		if (! isset($params["mesh_id"]) || !UUID::TryParse($params["mesh_id"], $meshID))
            RequestFailed('invalid or missing mesh identifier');
            
        $meshfile = $this->ComputeMeshFile($meshID);
        // Check to see if we already have the file
        if (! file_exists($meshfile))
        {
			// There is definitely a race condition here, the time between the check for an existing
            // file and acquiring a lock opens up the potential for overwriting the file
            $handle = fopen($meshfile,"wb");
            if (! flock($handle, LOCK_EX))
            {
                fclose($handle);
                unlink($meshfile);

                RequestFailed('unable to acquire lock on cache file');
            }
            
            // Pull it back from the asset service and store it locally
            if (! get_asset($meshID,$meshInfo))
            {
                flock($handle,LOCK_UN);
                fclose($handle);
                unlink($meshfile);

                log_message('error',"[GetMesh] mesh $meshID not found");
                print_r($meshInfo);
                RequestFailed('mesh not found');
            }
            
            //content type check
            if (empty(self::$MeshTypes[$meshInfo['ContentType']]))
            {
                flock($handle,LOCK_UN);
                fclose($handle);
                unlink($texturefile);

                log_message('error',sprintf('[GetMesh] wrong asset type; %s',$meshInfo['ContentType']));
                RequestFailed(sprintf('wrong asset type; %s',$meshInfo['ContentType']));
            }
			
			if (! fwrite($handle,$meshInfo['Content']))
            {
                flock($handle,LOCK_UN);
                fclose($handle);
                unlink($meshfile);

                log_message('error','[GetMesh] write to file failed');
                RequestFailed('unable to write cache file');
            }

            flock($handle,LOCK_UN);
            fclose($handle);
		}
		
		$this->SendMeshFile($meshID,$meshfile);
	}
}
