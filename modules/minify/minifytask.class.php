<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2020 Scavix Software GmbH & Co. KG
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
 * @copyright since 2020 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

class MinifyTask extends Task
{
    function Run($args)
    {
        log_warn("Syntax: minify-(all|js|css) <base-url> [<minify:(1|y|yes|0|n|no)> [<randomnc:(1|y|yes|0|n|no)>]]");
    }
    
    private $paths = false, $base_name=false, $minify=true, $randomnc=false, $base_url=false;
    var $Results = ['js'=>[],'css'=>[]];
    
    private function prepare($args=[])
    {
        if( !$this->base_url )
        {
            list($this->base_url) = $this->mapCliArgs($args,false,1);
            if( !$this->base_url )
            {
                log_warn("Syntax: minify-(all|js|css) <base-url> [<minify:(1|y|yes|0|n|no)> [<randomnc:(1|y|yes|0|n|no)>]]");
                \ScavixWDF\WdfException::Raise("Minify needs a base-url to fetch data from");
            }
            if( !ends_with($this->base_url,"/") ) $this->base_url .= "/";

            $old = $GLOBALS['CONFIG']['system']['url_root'];
            $GLOBALS['CONFIG']['system']['url_root'] = $this->base_url;

            foreach( $GLOBALS['CONFIG']['resources'] as $i=>$conf )
                $GLOBALS['CONFIG']['resources'][$i]['url'] = str_replace($old,$this->base_url, $conf['url']);

            log_debug("Base URL:",$GLOBALS['CONFIG']['system']['url_root']);
        }
        
        if( count($args) )
        {
            list($m) = $this->mapCliArgs($args,false,1);
            if( $m !== false )
            {
                $this->minify = $m!=0 && strtolower("$m")!='no' && strtolower("$m")!='n';
                if( !$this->minify )
                    log_debug("Minify disabled, only collecting files");
            }
        }
        if( count($args) )
        {
            list($m) = $this->mapCliArgs($args,false,1);
            $this->randomnc = $m!=0 && strtolower("$m")!='no' && strtolower("$m")!='n';
            if( $this->randomnc )
                log_debug("Using random NC");
        }
        if( !$this->paths )
        {
            global $CONFIG;
            $parts = array_diff($CONFIG['class_path']['order'], array('system','model','content'));
            $paths = array();
            foreach( $parts as $part )
                $paths = array_merge ($paths,$CONFIG['class_path'][$part]);

            sort($paths);
            $this->paths = array();
            foreach( $paths as $i=>$cp )
            {
                $root = true;
                for($j=0; $j<$i && $root; $j++)
                    if(starts_with($cp, $paths[$j]) )
                        $root = false;
                if( $root )
                    $this->paths[] = $cp;
            }
        }
        if( !$this->base_name )
        {
            $this->base_name = cfg_get('minify','target_path');
            system_ensure_path_ending($this->base_name,true);
            $this->base_name .= cfg_get('minify','base_name');
            $this->base_name .= (isSSL()?".1":".0");
            foreach( system_glob($this->base_name.".*.*") as $f )
                unlink($f);
            $nc = $this->randomnc?md5(time()):getAppVersion('nc');
            $this->base_name .= ".".preg_replace('/[^\d]*/', "", $nc);
        }
    }
    
    function All($args)
    {
        $this->prepare($args);
        $this->Js([]);
        $this->Css([]);
    }
    
    function Js($args)
    {
		$this->prepare($args);
        if( $this->minify ) unset($GLOBALS['nominify']); else $GLOBALS['nominify'] = '1';
        $this->Results['js'] = minify_js($this->paths, "{$this->base_name}.js");
        log_info(__METHOD__,"Processed these files:",$this->Results['js']);
    }
    
    function Css($args)
    {
		$this->prepare($args);
        if( $this->minify ) unset($GLOBALS['nominify']); else $GLOBALS['nominify'] = '1';
        $this->Results['css'] = minify_css($this->paths, "{$this->base_name}.css");
        log_info(__METHOD__,"Processed these files:",$this->Results['css']);
    }
}
