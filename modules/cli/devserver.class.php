<?php

use ScavixWDF\Tasks\Task;

/**
 * Starts a local PHP Webserver
 *
 * @see https://www.php.net/manual/en/features.commandline.webserver.php
 */
class DevServer extends Task
{
    function Run($args)
    {
        $endpoint = $this->getArg($args,0) ?: "127.0.0.1:80";
        $cmd = PHP_BINARY . " -S $endpoint ".__SCAVIXWDF__."/cli.php";
        $specs = array(
            0 => array("file", "php://stdin", "r"),
            1 => array("file", "php://stderr", "w"),
            2 => array("file", "php://stderr", "w")
        );
        $proc = proc_open($cmd, $specs, $pipes, getcwd());
        if (!is_resource($proc))
            return log_error("Unable to start PHP devserver");

        register_shutdown_function(function () use ($proc)
        {
            @proc_close($proc);
        });

        while( ($s = proc_get_status($proc)) && $s['running'] )
            usleep(100000);
    }
}
