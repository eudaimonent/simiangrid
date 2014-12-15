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

class xGetAsset implements IGridService
{
    public function Execute($db, $params)
    {
        $asset = null;
        $assetID = null;

        if (isset($params["ID"]) && UUID::TryParse($params["ID"], $assetID))
        {
            log_message('debug', "xGetAsset asset: $assetID");

            $assets = new SQLAssets($db);
            $asset = $assets->GetAsset($assetID);
        }
        
        $response = array();

        if (! empty($asset))
        {
            $response['Success'] = TRUE;
            $response['SHA256'] = $asset->SHA256;
            $response['Last-Modified'] = gmdate(DATE_RFC850, $asset->CreationDate);
            $response['CreatorID'] = $asset->CreatorID;
            $response['ContentType'] = $asset->ContentType;
            $response['ContentLength'] = $asset->ContentLength;
            $response['EncodedData'] = base64_encode($asset->Data);
	    $response['Temporary'] = $asset->Temporary;
        }
        else
        {
            log_message('info',"Asset $assetID not found");

            $response['Success'] = FALSE;
            $response['Message'] = "Asset $assetID not found";
        }

        header("Content-Type: application/json", true);
        echo json_encode($response);
        exit();
    }
}
