<?php

/**
 * Act as router script.
 * @see https://www.php.net/manual/en/features.commandline.webserver.php
 * @see <DevServer> Task
 */
if( PHP_SAPI == 'cli-server' )
{
    $log_router = function (...$args)
    {
        $args = implode("\t", array_map(function ($a)
        {
            return (!is_string($a) ? json_encode($a) : $a);
        }, $args));
        file_put_contents("php://stderr", date("[D M d H:i:s Y]") . " $args\n");
    };

    $_SERVER['WDF_FEATURES_NOCACHE'] = 'on';
    $_SERVER['WDF_FEATURES_REWRITE'] = 'on';

    $requested_script = $_SERVER['SCRIPT_FILENAME'];
    if( dirname($requested_script) == __DIR__ )
        $requested_script = __FILE__;

    if ($requested_script == __FILE__ || $_SERVER['SCRIPT_NAME'] == '/index.php' )
    {
        $requested_file = preg_replace('|(.*)/nc(\d+)/(.*)?(.*)|', '$1/$3', $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI']);
        if( is_file($requested_file) )
        {
            $requested_script = $requested_file;
        }
        else
        {
            $requested_script = $_SERVER['DOCUMENT_ROOT'] . '/index.php';

            // handle NC like in .htaccess
            $_SERVER['REQUEST_URI'] = preg_replace('|(.*)/nc(\d+)/(.*)?(.*)|', '$1/$3?nc=$2&$4', $_SERVER['REQUEST_URI']);

            // handle wdf_route like in .htaccess
            $args = explode("?", $_SERVER['REQUEST_URI'], 2);
            $wdf_route = ltrim(array_shift($args), '/');

            $_REQUEST['wdf_route'] = $_GET['wdf_route'] = $wdf_route;
        }
    }

    if (is_file($requested_script))
    {
        if (substr($requested_script, -4) == ".php")
        {
            $log_router("[REQUIRE] " . basename($requested_script),$_REQUEST);
            require_once($requested_script);
        }
        else
        {
            $log_router("[DELIVER] $requested_script ".$_SERVER['SCRIPT_NAME']);
            if (substr($requested_script, -4) == ".css")
                header("Content-Type: text/css");
            readfile($requested_script);
        }
    }
    else
    {
        $log_router("Internal Server Error",$requested_script,$_REQUEST,$_SERVER);
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ?$_SERVER['SERVER_PROTOCOL']: 'HTTP/1.1';
        header("$protocol 500 Internal Server Error", true, 500);
        die("500 Internal Server Error");
    }
    return;
}

/**
 * Act as CLI start script, this is when `php phar://path/to/scavix-wdf.phar` is executed.
 */
if( PHP_SAPI != 'cli' ) exit;
define("NO_CONFIG_NEEDED",true);
require(__DIR__.'/system.php');
system_init('scavix-wdf-cli');
\ScavixWDF\Model\DataSource::SetDefault("sqlite://:memory:");
if( class_exists("\\ScavixWDF\\CLI\\CliLogger") )
	\ScavixWDF\CLI\CliLogger::$LOG_SEVERITY = false;
classpath_add(getcwd());

if( count($GLOBALS['argv'])<2 )
	$GLOBALS['argv'][] = 'helptask';

\system_execute();