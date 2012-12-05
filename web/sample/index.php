<?
require_once(dirname(__FILE__)."/../system/system.php");
define("APP_VERSION", "0.0.1");
define("_nc", "_nc=".str_replace(".", "", APP_VERSION));

system_init('sample');

Model::$DefaultDatasource = model_datasource('system');

system_execute();
