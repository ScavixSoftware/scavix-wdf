<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

/**
 * @internal CLI only Task run `php index.php create` for info
 */
class CreateTask extends Task
{
    /**
     * @ovderride <Task::Run>
     */
    function Run($args)
    {
        log_warn("Syntax: create-app name=<appname> root=<folder>");
    }

    /**
     * Creates a application stub to start development.
     *
     * @param array $args commandline arguments
     * @return void
     */
    function App($args)
    {
        $name = ifavail($args,'name');
        $root = ifavail($args,'root');
        if( !$name || !$root )
            return $this->Run($args);

        $rp_root = realpath($root);
        if( !file_exists($rp_root) )
            return log_error("Root folder does not exist: '$root'");
        if( !is_dir($rp_root) )
            return log_error("Root is not a folder: '$root'");
        if( !is_writable($rp_root) )
            return log_error("Root folder is not writable: '$root'");

        $root = $rp_root;
        $basename = ucwords($name)."Base";

        $docroot = "$root/docroot";
        $strings = "$root/strings";
        $logs    = "$root/logs";

        foreach( [$docroot,$strings,$logs] as &$f )
        {
            //log_debug("folder $f");
            if( !file_exists($f) && !mkdir($f) )
                return log_error("Cannot create folder: '$f'");
            $f = realpath($f);
        }
        if( count(glob("$docroot/*.*"))>0 )
            return log_error("DocRoot is not empty, terminating");

        $in_phar = (stripos(__DIR__,"phar://") !== false);
        $sys = $in_phar
            ?dirname(__DIR__)."/system.php"
            :realpath(__DIR__."/../system.php");

        file_put_contents("$docroot/.htaccess","<IfModule mod_rewrite.c>
	RewriteEngine On

	# redirect NoCache files to the real ones
	SetEnv WDF_FEATURES_NOCACHE on
	RewriteRule (.*)/nc([0-9]+)/(.*) $1/$3?_nc=$2 [L,QSA]

	# redirect inexistant requests to index.php
	SetEnv WDF_FEATURES_REWRITE on
    RewriteCond %{REQUEST_FILENAME} .*\.less$ [NC,OR]
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteCond %{REQUEST_URI} !index.php
	RewriteRule (.*) index.php?wdf_route=$1 [L,QSA]
</IfModule>");

        file_put_contents("$docroot/index.php","<?php
require('$sys');
system_init('$name');
\ScavixWDF\Model\DataSource::SetDefault('sqlite://:memory:');
system_execute();
");

        file_put_contents("$docroot/config.php","<?php
\$CONFIG['system']['default_page']  = '{$basename}';
\$CONFIG['system']['default_event'] = 'Init';

\$CONFIG['translation']['data_path'] = __DIR__.'/../strings';

ini_set('error_log', __DIR__.'/../logs/error_log');
\$CONFIG['system']['logging']['log'] = [
    'path' => __DIR__.'/../logs/',
    'filename_pattern' => 'error.log',
    'log_severity' => true,
    'max_filesize' => 10000000,
    'keep_for_days' => 30,
    'max_trace_depth' => 16,
];

classpath_add(__DIR__);");

        file_put_contents("$docroot/".strtolower($basename).".class.php","<?php
class $basename extends \ScavixWDF\Base\Htmlpage
{
    function Init()
    {
        log_debug('Your application <$name> is ready.');
        \$this->content('<p>Your application <b>$name</b> is ready.</p>');
    }
}
");
    }
}
