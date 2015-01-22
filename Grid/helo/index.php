<?php

define('COMMONPATH', str_replace("\\", "/", realpath(dirname(__FILE__) . '/../..') . '/GridCommon/'));
define('BASEPATH', str_replace("\\", "/", realpath(dirname(__FILE__)) . '/../'));

require_once(COMMONPATH . 'Config.php');
require_once(COMMONPATH . 'Errors.php');
require_once(COMMONPATH . 'Log.php');

log_message('debug','hypergrid helo request from ' . $_SERVER['REMOTE_ADDR']);
header("X-Handlers-Provided: opensim-simian");

exit();
?>