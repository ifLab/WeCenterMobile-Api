<?php
define('BASEPATH', realpath(dirname(__FILE__)).'/');
define('APPPATH', realpath(dirname(__FILE__)).'');
include_once('core/Common.php');
include_once('database/DB.php');
function get_config(){}

function &load_database($params = '', $active_record_override = false)
{
	$database =& DB($params, $active_record_override);
	return $database;
}

include_once('./lib/config.php');
include_once('./lib/functions.inc.php');
/* Load database via Database source name, eg. "mysql://root:password@localhost/mydatabase" */
$db =& load_database("mysql://".DB_USER.":".DB_PWD."@".DB_HOST."/".DB_NAME, true);