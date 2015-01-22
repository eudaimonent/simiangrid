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
require_once(COMMONPATH . 'Types.php');

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
class WebFetchInventoryRequest
{
    public $FolderID = null;
    public $OwnerID = null;

    public $FetchFolders = 0;
    public $FetchItems = 0;
    public $SortOrder = 0;

    public function __construct($request)
    {
        $this->FromArray($request);
    }

    public function FromArray($request)
    {
        $this->FetchFolders = empty($request['fetch_folders']) ? 0 : $request['fetch_folders'];
        $this->FetchItems = empty($request['fetch_items']) ? 0 : $request['fetch_items'];
        $this->SortOrder = empty($request['sort_order']) ? 0 : $request['sort_order'];

        $this->FolderID = new llsd_UUID;
        if (! empty($request['folder_id']))
            $this->FolderID->Set($request['folder_id']);

        $this->OwnerID = new llsd_UUID;
        if (! empty($request['owner_id']))
            $this->OwnerID->Set($request['owner_id']);
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
class WebFetchInventoryCategory
{
    public $FolderID = null;
    public $ParentID = null;

    public $Name = "";
    public $Type = 0;
    public $PreferredType = -1;

    public $Version = 0;

    public function __construct()
    {
        $this->FolderID = new llsd_UUID;
        $this->ParentID = new llsd_UUID;
    }

    public function CopyFromSimianItem($item)
    {
        $TypeConverter = new SimianTypeConverter;

        $this->FolderID->Set($item['ID']);
        $this->ParentID->Set($item['ParentID']);
        $this->Name = $item['Name'];

        //$mimes =& get_mimes();
        if (! empty($item['ExtraData']['LinkedItemType']))
        {
            //$type = isset($mimes[$item['ExtraData']['LinkedItemType']]) ? $mimes[$item['ExtraData']['LinkedItemType']] : -1;
            $type = $TypeConverter->Content2Asset($item['ExtraData']['LinkedItemType']);
            $this->Type = $type;
        }
        else
        {
            //$type = isset($mimes[$item['ContentType']]) ? $mimes[$item['ContentType']] : -1;
            $type = $TypeConverter->Content2Asset($item['ContentType']);
            $this->Type = $type;
        }

        if (! empty($item['Version']))
            $this->Version = intval($item['Version']);
    }

    public function ToArray()
    {
        $result = array();
        $result['name'] = $this->Name;
        $result['type'] = $this->Type;
        $result['preferred_type'] = $this->PreferredType;
        $result['folder_id'] = $this->FolderID;
        $result['parent_id'] = $this->ParentID;

        // $result['version'] = $this->Version;

        return $result;
    }        
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
class WebFetchInventoryPermission
{
    public $BaseMask = 0;
    public $EveryoneMask = 0;
    public $GroupMask = 0;
    public $NextOwnerMask = 0;
    public $OwnerMask = 0;

    public $IsOwnerGroup = false;

    public $OwnerID = null;
    public $CreatorID = null;
    public $GroupID = null;
    public $LastOwnerID = null;

    public function __construct()
    {
        $this->OwnerID = new llsd_UUID;
        $this->CreatorID = new llsd_UUID;
        $this->GroupID = new llsd_UUID;
        $this->LastOwnerID = new llsd_UUID;
    }

    public function ToArray()
    {
        $result = array();

        $result['creator_id'] = $this->CreatorID;
        $result['owner_id'] = $this->OwnerID;
        $result['group_id'] = $this->GroupID;

        $result['base_mask'] = $this->BaseMask;
        $result['owner_mask'] = $this->OwnerMask;
        $result['group_mask'] = $this->GroupMask;
        $result['everyone_mask'] = $this->EveryoneMask;
        $result['next_owner_mask'] = $this->NextOwnerMask;
        $result['is_owner_group'] = (boolean)($this->IsOwnerGroup);

        //$result['last_owner_id'] = $this->LastOwnerID;

        return $result;
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
class WebFetchInventoryItem
{
    // public $AgentID = null;
    public $AssetID = null;
    public $ItemID = null;
    public $ParentID = null;

    public $CreatedAt = 0;
    public $Name = "";
    public $Desc = "";
    public $Flags = 0;
    public $Type = 0;
    public $InventoryType = 0;

    public $Permissions = null;
    public $SaleInfo = null;

    public $Version = 0;

    public function __construct()
    {
        // $this->AgentID = new llsd_UUID;
        $this->AssetID = new llsd_UUID;
        $this->ItemID = new llsd_UUID;
        $this->ParentID = new llsd_UUID;

        $this->Permissions = new WebFetchInventoryPermission;
        $this->SaleInfo = array("sale_price" => 0, "sale_type" => 0);
    }

    public function FollowLink($ownerID)
    {
        $TypeConverter = new SimianTypeConverter;
        $linktype = $TypeConverter->Content2Asset('application/vnd.ll.link');
        $linkfoldertype = $TypeConverter->Content2Asset('application/vnd.ll.linkfolder');

        // Some useful constants
        //$mimes =& get_mimes();
        //$linktype = $mimes['application/vnd.ll.link'];

        if ($this->Type != $linktype)
            return null;

        if (! get_inventory_items($ownerID,$this->AssetID,$items,1,1,1))
        {
            log_message('warn',sprintf('unable to locate inventory item %s',$this->AssetID));
            return null;
        }

        if (count($items) != 1)
        {
            log_message('warn',sprintf('broken link to %s from %s',$this->AssetID,$this->ItemID));
            return null;
        }

        $link = new WebFetchInventoryItem;
        $link->CopyFromSimianItem($items[0]);
        return $link;
    }

    public function CopyFromSimianItem($item)
    {
        $TypeConverter = new SimianTypeConverter;
        
        $this->AssetID->Set($item['AssetID']);
        $this->ItemID->Set($item['ID']);
        $this->ParentID->Set($item['ParentID']);
        $this->CreatedAt = intval($item['CreationDate']);
        $this->Name = $item['Name'];
        $this->Desc = $item['Description'];
        $this->Flags = empty($item['ExtraData']['Flags']) ? 0 : $item['ExtraData']['Flags'];

        if (! empty($item['Version']))
            $this->Version = intval($item['Version']);

        //$mimes =& get_mimes();
        if (! empty($item['ExtraData']['LinkedItemType']))
        {
            //$type = isset($mimes[$item['ExtraData']['LinkedItemType']]) ? $mimes[$item['ExtraData']['LinkedItemType']] : -1;
            $type = $TypeConverter->Content2Asset($item['ExtraData']['LinkedItemType']);
            $this->Type = $type;
            
            // $itype = isset($mimes[$item['ContentType']]) ? $mimes[$item['ContentType']] : -1;
            $itype = $TypeConverter->Content2Inventory($item['ContentType']);
            $this->InventoryType = $itype;
        }
        else
        {
            //$type = isset($mimes[$item['ContentType']]) ? $mimes[$item['ContentType']] : -1;
            $type = $TypeConverter->Content2Asset($item['ContentType']);
            $this->Type = $type;

            $itype = $TypeConverter->Content2Inventory($item['ContentType']);
            $this->InventoryType = $itype;
        }

        if (! empty($item['ExtraData']['SalePrice']))
            $this->SaleInfo['sale_price'] = intval($item['ExtraData']['SalePrice']);

        if (! empty($item['ExtraData']['SaleType']))
            $this->SaleInfo['sale_type'] = intval($item['ExtraData']['SaleType']);

        if (! empty($item['ExtraData']['Permissions']))
        {
            $this->Permissions->OwnerID->Set($item['OwnerID']);
            $this->Permissions->LastOwnerID->Set($item['OwnerID']);
            $this->Permissions->CreatorID->Set($item['CreatorID']);

            if (! empty($item['ExtraData']['GroupID']))
                $this->GroupID->Set($item['ExtraData']['GroupID']);

            $parray = $item['ExtraData']['Permissions'];
            $this->Permissions->BaseMask = empty($parray['BaseMask']) ? 0 : $parray['BaseMask'];
            $this->Permissions->EveryoneMask = empty($parray['EveryoneMask']) ? 0 : $parray['EveryoneMask'];
            $this->Permissions->GroupMask = empty($parray['GroupMask']) ? 0 : $parray['GroupMask'];
            $this->Permissions->NextOwnerMask = empty($parray['NextOwnerMask']) ? 0 : $parray['NextOwnerMask'];
            $this->Permissions->OwnerMask = empty($parray['OwnerMask']) ? 0 : $parray['OwnerMask'];
            $this->Permissions->IsOwnerGroup = (boolean)(empty($parray['GroupOwned']) ? 0 : $parray['GroupOwned']);
        }
    }

    public function ToArray()
    {
        $result = array();

        // $result['agent_id'] = $this->AgentID;
        $result['parent_id'] = $this->ParentID;
        $result['asset_id'] = $this->AssetID;
        $result['item_id'] = $this->ItemID;

        $result['permissions'] = $this->Permissions->ToArray();
        $result['type'] = $this->Type;
        $result['inv_type'] = $this->InventoryType;
        $result['flags'] = $this->Flags;
        $result['sale_info'] = $this->SaleInfo;
        $result['name'] = $this->Name;
        $result['desc'] = $this->Desc;
        $result['created_at'] = $this->CreatedAt;

        //$result['version'] = $this->Version;
        return $result;
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
class WebFetchInventoryResponse
{
    public $AgentID = null;
    public $OwnerID = null;
    public $FolderID = null;

    public $Version = 0;
    public $Descendents = 0;

    public $Categories = null;
    public $Items = null;

    public function __construct()
    {
        $this->AgentID = new llsd_UUID;
        $this->OwnerID = new llsd_UUID;
        $this->FolderID = new llsd_UUID;
        $this->Categories = array();
        $this->Items = array();
    }

    public function AddCategory($category)
    {
        array_push($this->Categories,$category);
        // $this->Descendents++;
    }

    public function AddItem($item)
    {
        array_push($this->Items,$item);
        // $this->Descendents++;
    }

    public function ToArray()
    {
        $result = array();
        $result['agent_id'] = $this->AgentID;
        $result['descendents'] = $this->Descendents;
        $result['folder_id'] = $this->FolderID;

        $result['categories'] = array();
        foreach ($this->Categories as $cat)
            array_push($result['categories'],$cat->ToArray());

        $result['items'] = array();
        foreach ($this->Items as $item)
            array_push($result['items'],$item->ToArray());

        $result['owner_id'] = $this->OwnerID;
        $result['version'] = $this->Version;

        return $result;
    }
}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
class WebFetchInvDesc implements IPublicService
{
    private function WebFetchInventoryReply($req)
    {
        // Some useful constants
        //$mimes =& get_mimes();
        //$linktype = $mimes['application/vnd.ll.link'];
        //$linkfoldertype = $mimes['application/vnd.ll.linkfolder'];

        $TypeConverter = new SimianTypeConverter;
        $linktype = $TypeConverter->Content2Asset('application/vnd.ll.link');
        $linkfoldertype = $TypeConverter->Content2Asset('application/vnd.ll.linkfolder');

        $res = new WebFetchInventoryResponse;

        $res->AgentID->Set($req->OwnerID);
        $res->OwnerID->Set($req->OwnerID);
        $res->FolderID->Set($req->FolderID);

        $res->Version = 0;
        $res->Descendents = 0;

        if ($req->FolderID == null || $req->FolderID->IsNull())
        {
            $res->Version = 1;
            return $res;
        }

        if (! get_inventory_items($req->OwnerID,$req->FolderID,$items,1,1,1))
        {
            log_message('error','failed to retrieve inventory for ' . $req->FolderID);
            RequestFailed('Failed to retrieve inventory for folder ' . $req->FolderID);
        }

        $links = array();
        
        foreach (array_reverse($items) as $item)
        {
            if ($item['ID'] == $req->FolderID)
            {
                $res->Version = intval($item['Version']);
                continue;
            }
            
            if ($item['Type'] == 'Item')
            {
                //log_message('error',sprintf("found item %s",$item['ID']));

                $invitem = new WebFetchInventoryItem;
                $invitem->CopyFromSimianItem($item);

                if ($invitem->Type == $linktype)
                {
                    // this is a link & goes on to the link array
                    // array_push($links,$invitem);
                    array_unshift($links,$invitem);
                    
                    $link = $invitem->FollowLink($req->OwnerID);
                    if ($link == null)
                    {
                        log_message('warn',sprintf('unable to retrieve linked inventory item %s',$invitem->ItemID));
                    }
                    else
                    {
                        // this is the referred to item and goes into the items right away
                        $res->AddItem($link);
                    }
                }
                else
                {
                    $res->AddItem($invitem);
                }
            }
            else if ($item['Type'] == 'Folder')
            {
                //log_message('error',sprintf("found folder %s",$item['ID']));

                $invcat = new WebFetchInventoryCategory;
                $invcat->CopyFromSimianItem($item);
                $res->AddCategory($invcat);

                if ($invcat->Type == $linkfoldertype)
                {
                }
            }
            else 
            {
                log_message('error',"unknown type; " . $item['Type']);
            }
        }

        foreach ($links as $link)
            $res->AddItem($link);

        $res->Descendents = count($res->Categories) + count($res->Items) - count($links);
        return $res;
    }

    // -----------------------------------------------------------------
    public function Execute($params)
    {
        $config = &get_config();
        if (empty($config['authorize_commands']) && empty($config['override_commands']['WebFetchInvDesc']))
        {
            log_message('warn','Attempt to invoke protected command; WebFetchInvDesc');
            RequestFailed('Attempt to invoke protected command');
        }

        if (empty($params['folders']))
        {
            log_message('error','No folders supplied');
            RequestFailed('Missing required folder parameter for WebFetchInvDesc');
        }

        $folders = array();

        foreach ($params['folders'] as $request)
        {
            $req = new WebFetchInventoryRequest($request);
            $res = $this->WebFetchInventoryReply($req);
            
            array_push($folders,$res->ToArray());
        }

        $result['folders'] = $folders;

        header("Content-Type: application/llsd+xml", true);
        echo llsd_encode($result);
        exit();
    }
}
