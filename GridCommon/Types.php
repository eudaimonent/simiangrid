<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(COMMONPATH . 'Log.php');

/*
|--------------------------------------------------------------------------
| MIME Types
|--------------------------------------------------------------------------
|
| This file contains an array of mime types to SL inventory type. It is
| used by the login service to build an SL-compatible inventory skeleton
|
*/

class SimianTypeConverter
{
    private $InventoryTypeEnums = array(
        'Unknown' => -1,
        'Texture' => 0,
        'Sound' => 1,
        'CallingCard' => 2,
        'Landmark' => 3,
        'Object' => 6,
        'Notecard' => 7,
        'Category' => 8,
        'Folder' => 8,
        'RootCategory' => 9,
        'LSL' => 10,
        'Snapshot' => 15,
        'Attachment' => 17,
        'Wearable' => 18,
        'Animation' => 19,
        'Gesture' => 20,
        'Mesh' => 22,
        );

    private $AssetTypeEnums = array(
        'Unknown' => -1,
        'Texture' => 0,
        'Sound' => 1,
        'CallingCard' => 2,
        'Landmark' => 3,
        'Clothing' => 5,
        'Object' => 6,
        'Notecard' => 7,
        'Folder' => 8,
        'RootFolder' => 9,
        'LSLText' => 10,
        'LSLBytecode' => 11,
        'TextureTGA' => 12,
        'Bodypart' => 13,
        'TrashFolder' => 14,
        'SnapshotFolder' => 15,
        'LostAndFoundFolder' => 16,
        'SoundWAV' => 17,
        'ImageTGA' => 18,
        'ImageJPEG' => 19,
        'Animation' => 20,
        'Gesture' => 21,
        'Simstate' => 22,
        'FavoriteFolder' => 23,
        'Link' => 24,
        'LinkFolder' => 25,
        'EnsembleStart' => 26,
        'EnsembleEnd' => 45,
        'CurrentOutfitFolder' => 46,
        'OutfitFolder' => 47,
        'MyOutfitsFolder' => 48,
        'Mesh' => 49,
        'Inbox' => 50,
        'Outbox' => 51,
        'BasicRoot' => 51
        );

    private $ContentToAssetMap = array();
    private $ContentToInventoryMap = array();

    // -----------------------------------------------------------------
    // -----------------------------------------------------------------
    private function RegisterType($atypestr, $itypestr, $ctypestr)
    {
        $atype = empty($this->AssetTypeEnums[$atypestr]) ? -1 : $this->AssetTypeEnums[$atypestr];
        $itype = empty($this->InventoryTypeEnums[$itypestr]) ? -1 : $this->InventoryTypeEnums[$itypestr];

        if (empty($this->ContentToAssetMap[$ctypestr]))
            $this->ContentToAssetMap[$ctypestr] = $atype;

        if (empty($this->ContentToInventoryMap[$ctypestr]))
            $this->ContentToInventoryMap[$ctypestr] = $itype;
    }
    
    // -----------------------------------------------------------------
    // -----------------------------------------------------------------
    public function __construct()
    {
        $this->RegisterType('Unknown','Unknown',"application/octet-stream");
        $this->RegisterType('Texture','Texture',"image/x-j2c");
        $this->RegisterType('Texture','Snapshot',"image/x-j2c");
        $this->RegisterType('TextureTGA','Texture',"image/tga");
        $this->RegisterType('ImageTGA','Texture',"image/tga");
        $this->RegisterType('ImageJPEG','Texture',"image/jpeg");
        $this->RegisterType('Sound','Sound',"audio/ogg");
        $this->RegisterType('SoundWAV','Sound',"audio/x-wav");
        $this->RegisterType('CallingCard','CallingCard',"application/vnd.ll.callingcard");
        $this->RegisterType('Landmark','Landmark',"application/vnd.ll.landmark");
        $this->RegisterType('Clothing','Wearable',"application/vnd.ll.clothing");
        $this->RegisterType('Object','Object',"application/vnd.ll.primitive");
        $this->RegisterType('Object','Attachment',"application/vnd.ll.primitive");
        $this->RegisterType('Notecard','Notecard',"application/vnd.ll.notecard");
        $this->RegisterType('Folder','Folder',"application/vnd.ll.folder");
        $this->RegisterType('RootFolder','RootCategory',"application/vnd.ll.rootfolder");
        $this->RegisterType('LSLText','LSL',"application/vnd.ll.lsltext");
        $this->RegisterType('LSLBytecode','LSL',"application/vnd.ll.lslbyte");
        $this->RegisterType('Bodypart','Wearable',"application/vnd.ll.bodypart");
        $this->RegisterType('TrashFolder','Folder',"application/vnd.ll.trashfolder");
        $this->RegisterType('SnapshotFolder','Folder',"application/vnd.ll.snapshotfolder");
        $this->RegisterType('LostAndFoundFolder','Folder',"application/vnd.ll.lostandfoundfolder");
        $this->RegisterType('Animation','Animation',"application/vnd.ll.animation");
        $this->RegisterType('Gesture','Gesture',"application/vnd.ll.gesture");
        $this->RegisterType('Simstate','Snapshot',"application/x-metaverse-simstate");
        $this->RegisterType('FavoriteFolder','Unknown',"application/vnd.ll.favoritefolder");
        $this->RegisterType('Link','Unknown',"application/vnd.ll.link");
        $this->RegisterType('LinkFolder','Unknown',"application/vnd.ll.linkfolder");
        $this->RegisterType('CurrentOutfitFolder','Unknown',"application/vnd.ll.currentoutfitfolder");
        $this->RegisterType('OutfitFolder','Unknown',"application/vnd.ll.outfitfolder");
        $this->RegisterType('MyOutfitsFolder','Unknown',"application/vnd.ll.myoutfitsfolder");
        $this->RegisterType('Mesh','Mesh',"application/vnd.ll.mesh");
    }

    // -----------------------------------------------------------------
    // -----------------------------------------------------------------
    public function Content2Inventory($ctype)
    {
        $itype = empty($this->ContentToInventoryMap[$ctype]) ? -1 : $this->ContentToInventoryMap[$ctype];
        //log_message('warn',sprintf('mapped inventory type %s --> %d',$ctype,$itype));
        
        return $itype;
    }

    // -----------------------------------------------------------------
    // -----------------------------------------------------------------
    public function Content2Asset($ctype)
    {
        $atype = empty($this->ContentToAssetMap[$ctype]) ? -1 : $this->ContentToAssetMap[$ctype];
        //log_message('warn',sprintf('mapped asset type %s --> %d',$ctype,$atype));
        
        return $atype;
    }
}

