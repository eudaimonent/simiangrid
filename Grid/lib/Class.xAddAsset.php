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
 * @package    SimianGrid
 * @author     Jim Radford <http://www.jimradford.com/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */
require_once(COMMONPATH . 'SQLAssets.php');
//require_once(COMMONPATH . 'MongoAssets.php');
//require_once(COMMONPATH . 'FSAssets.php');

class xAddAsset implements IGridService
{
    // Required Parameters
    //    EncodedData -- base64 encoded asset data
    //    ContentType -- MIME type for the asset
    //
    // Optional Parameters
    //    AssetID -- preset asset UUID, for updating assets
    //    CreatorID -- UUID of the asset creator
    //    Temporary -- flag to indicate that the asset is temporary
    //    Public -- flag to indicate that the asset is public
    //
    // Response
    //    Success -- status of the operation
    //    Message -- set of the operation failed
    //    Status -- indicates whether the asset was created or updated
    //    AssetID -- uuid of the asset added to the database

    public function Execute($db, $params)
    {
        $asset = null;
        $assetID = null;

        $response = array();

        if (isset($params["EncodedData"]) && isset($params["ContentType"]))
        {
            log_message('debug', "xAddAsset asset");

            // Build the asset structure from the parameters
            $asset = new Asset();

            if (!isset($params["AssetID"]) || !UUID::TryParse($params["AssetID"],$asset->ID))
                $asset->ID = UUID::Random();

            if (!isset($params["CreatorID"]) || !UUID::TryParse($params["CreatorID"],$asset->CreatorID))
                $asset->CreatorID = UUID::Zero;

            $asset->Data = base64_decode($params["EncodedData"]);
            $asset->SHA256 = hash("sha256",$asset->Data);
            $asset->ContentLength = strlen($asset->Data);
            $asset->ContentType = $params["ContentType"];
            $asset->Temporary = ! empty($params["Temporary"]);
            $asset->Public = ! empty($params["Public"]);

            $assets = new SQLAssets($db);

            $created = false;
            if ($assets->AddAsset($asset, $created))
            {
                $response['Success'] = TRUE;
                $response['AssetID'] = $asset->ID;
                $response['Status'] = $created ? "created" : "updated";
            }
            else
            {
                log_message('warn','failed to create asset');
                $response['Success'] = FALSE;
                $response['Message'] = 'failed to create the asset';
            }
        }
        else
        {
            $response['Success'] = FALSE;
            $response['Message'] = 'missing required parameters';
        }
            
        log_message('debug', sprintf("[AddAsset] result %s",json_encode($response)));
        
        header("Content-Type: application/json", true);
        echo json_encode($response);
        exit();
    }
}
