<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Service URLs
|--------------------------------------------------------------------------
|
| Enter the URL of each SimianGrid service below
|
*/
$config['user_service'] = "@@USER_SERVICE@@";
$config['grid_service'] = "@@GRID_SERVICE@@";
$config['asset_service'] = "@@ASSET_SERVICE@@";
$config['inventory_service'] = "@@INVENTORY_SERVICE@@";
$config['map_service'] = "@@MAP_SERVICE@@";

/*
|--------------------------------------------------------------------------
| Hypergrid Service URLs
|--------------------------------------------------------------------------
|
| By default these are equivalent to the service URLs. However, the host name
| must be an external hostname. Override if you use "localhost" for your 
| service URLs
|
*/
$config['hg_user_service'] = $config['user_service'];
$config['hg_asset_service'] = $config['asset_service'];
$config['hg_inventory_service'] = $config['inventory_service'];
$config['hypergrid_uri'] = $config['grid_service'] . 'hypergrid.php';

//Default Region for HG
$config['hypergrid_default_region'] = "OpenSim Test";

/*
|--------------------------------------------------------------------------
| Error Logging Threshold
|--------------------------------------------------------------------------
|
| You can enable error logging by setting a threshold over zero. The
| threshold determines what gets logged. Threshold options are:
|
|	0 = Disables logging, Error logging TURNED OFF
|	1 = Error Messages (including PHP errors)
|   2 = Warning Messages
|	3 = Informational Messages
|	4 = Debug Messages
|
| For a live site you'll usually only enable Errors (1) to be logged otherwise
| your log files will fill up very fast.
|
*/
$config['log_threshold'] = 1;

/*
|--------------------------------------------------------------------------
| Map Tile Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| map/ folder. Use a full server path with trailing slash. This directory should
| map to the URL specified in $config['map_service'] above
|
*/
$config["map_path"] = "";


/*
|--------------------------------------------------------------------------
| Texture Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| textures/ folder. Use a full server path with trailing slash.
|
*/
$config["texture_path"] = "";
$config["texture_split_size"] = 2;
$config["texture_split_depth"] = 2;

/*
|--------------------------------------------------------------------------
| Mesh Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| meshes/ folder. Use a full server path with trailing slash.
|
*/
$config["mesh_path"] = "";
$config["mesh_split_size"] = 2;
$config["mesh_split_depth"] = 2;


/*
|--------------------------------------------------------------------------
| Error Logging Directory Path
|--------------------------------------------------------------------------
|
| Leave this BLANK unless you would like to set something other than the default
| logs/ folder. Use a full server path with trailing slash.
|
*/
$config['log_path'] = "";

/*
|--------------------------------------------------------------------------
| Time Zone
|--------------------------------------------------------------------------
|
| You can change this to a PHP-supported timezone to write log files with
| your local timezone instead of UTC. http://php.net/manual/en/timezones.php
| has a list of supported timezone names.
|
*/
date_default_timezone_set("UTC");

/*
|--------------------------------------------------------------------------
| Date Format for Logs
|--------------------------------------------------------------------------
|
| Each item that is logged has an associated date. You can use PHP date
| codes to set your own date formatting
|
*/
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
|--------------------------------------------------------------------------
| Authorize Commands
|--------------------------------------------------------------------------
|
| Use capabilities to authorize commands, default is to authorize
| all operations regardless of the capability provided
|
*/
$config['authorize_commands'] = false;

