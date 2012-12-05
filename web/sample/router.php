<?
if (file_exists(__DIR__ . '/' . $_SERVER['REQUEST_URI']))
{
	return false;
}
else
{
	$m = explode("/nc",$_SERVER['REQUEST_URI'],2);
	if( count($m) == 2 )
	{
		$m = explode("/",$m[1],2);
		if( count($m) == 2 )
		{
			define("_nc", "0");
			require_once(dirname(__FILE__)."/../system/system.php");
			system_init('sample');
			$file = resFile($m[1],true);
			if( $file )
			{
				readfile($file);
				die();
			}
		}
	}
	include_once("index.php");
}