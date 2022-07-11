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
namespace ScavixWDF;

require_once(__DIR__.'/lessphp/lessc.inc.php');

/**
 * This class creates a unique interface for LESS compilers.
 * 
 * Currently just inherited from lessc, it may be used to add more abstraction
 * when another compiler is used.
 * 
 * Some notes about LESS variables: There's a priority and some extensions to the LESS syntax.
 * This is the variable prio, highest first:
 *   1. Defined in less file with: @<varname>: force(<value>);
 *   2. Defined via <register_less_variable> function
 *   3. Defined in $CONFIG['less']['variables']
 *   4. Defined in less file with: @<varname>: register(<value>);
 *   5. Defined in less file with: @<varname>: <value>;
 * This implies, that variables are not overwritten, if they were defined as in 1-4.
 * Normal assignments (as in 5) will allow later overwriting as usual.
 * 
 * More config bases options are:
 * - En-/Disable verbose logging: $CONFIG['less']['verbose'] = true|false (default: false)
 * - Add global less files (injected in every file): $CONFIG['less']['files'] = ['/path/to/file.less']
 * 
 * Other LESS enhancements:
 * - Add "verbose_compilation = on" as comment somewhere in a less file to enable verbose logging
 * - Add "verbose_compilation = off" to disable it
 * - "background: lightenBackground(<color>[ <opacity>])" to have a light-colored background with opacity
 * - "background: darkenBackground(<color>[ <opacity>])" to have a dark-colored background with opacity
 * - "<img-prop>: dataUri(<res_filename>)" will embed the contents of a resource-file data uri (automatically using the best encoding)
 */
class LessCompiler extends \lessc implements \JsonSerializable
{
    private $id, $injected, $verbose, $injecting;
    
    function __construct($fname = null)
    {
        parent::__construct($fname);
        $this->id = random_int(999, 9999);
        $this->injected = [];
        $this->verbose = cfg_getd('less','verbose',false);
        
        $enhancedBackgound = function($a,$mode)
        {
            $color = '#fff';
            $opacity = '0.7';
            $getVal = function($v)use(&$color,&$opacity)
            {
                if( $v[0] == 'color' )
                    $color = Base\Color::rgba($v[1], $v[2], $v[3]);
                elseif( $v[0] == 'function' && $v[1] == 'var' )
                {
                    $v = $v[2][2];
                    $color = "var({$v[0][1]})";
                }
                elseif( $v[0] == 'number' )
                    $opacity = $v[1];
            };
            if( $a[0] == 'list' )
                foreach( $a[2] as $v )
                    $getVal($v);
            else
                $getVal($a);
            
            $svg_col = Base\Color::hex($mode=='lighten'?'white':'black')->setAlpha($opacity);
            
            return implode(", ",[
                "linear-gradient(to right,$color 0%,$color 100%)",
                "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1' style='background:$svg_col'%3E%3C/svg%3E%0A\")"
            ]).";background-blend-mode:$mode;";
        };
        
        $this->registerFunction('lightenBackground', function($a)use($enhancedBackgound){ return $enhancedBackgound($a,'lighten'); });
        $this->registerFunction('darkenBackground', function($a)use($enhancedBackgound){ return $enhancedBackgound($a,'darken'); });
        $this->registerFunction('dataUri', function($a)
        {
            if( $a[0] != 'string' )
                return "none";
            
            $fn = resFile(trim($a[2][0],"\"' "), true);
            if( !file_exists($fn) )
                return "none";
            
            $mime = system_guess_mime($fn);
            if( "image/svg+xml" == $mime )
            {
                $c = file_get_contents($fn);
                $c = preg_replace('/<!--.*-->/', '', $c);
                $c = preg_replace('/[\r\n\t]/', ' ', $c);
                $c = preg_replace('/\s\s+/', ' ', $c);
                $c = str_replace(
                    ['"',"%"  ,"#"  ,'{'  ,'}'  ,'<'  ,'>'],
                    ["'","%25","%23","%7B","%7D","%3C","%3E"], 
                    $c);
                return "url(\"data:$mime,$c\")";
            }
            $c = base64_encode(file_get_contents($fn));
            return "url(\"data:$mime;base64,$c\")";
        });
    }
    
    /**
     * Set verbosity on/off.
     * 
     * @param bool $on True=on, False=off
     */
    function setVerbose($on=true)
    {
        $this->verbose = $on;
    }
    
    function debug(...$args)
    {
        if( $this->verbose )
            log_debug("[LESS][$this->id]",...$args);
    }
    
    function error(...$args)
    {
        log_error("[LESS][$this->id]",...$args);
    }
    
    function makeParser($name): \lessc_parser
    {
        /**
         * Anonymous class acting as LESS parser
         */
        return new class($this,$name,$this->preserveComments) extends \lessc_parser
        {
            public function __construct($compiler,$name,$preserveComments)
            {
                parent::__construct($compiler,$name);
                $this->writeComments = $preserveComments;
            }
            
            /**
             * @internal Overwritten to handle in-file verbosity flags
             */
            function parse($str = null, $initialVariables = null)
            {
                if( stripos("$str","verbose_compilation = on") !== false )
                {
                    $this->lessc->setVerbose();
                }
                elseif( stripos("$str","verbose_compilation = off") !== false )
                {
                    $this->lessc->setVerbose(false);
                }
                return parent::parse($str, $initialVariables);
            }
        };
    }
    
    /**
     * @internal Compiles a LESS file to $outfile
     */
    function compileFile($fname, $outFname = null)
    {
        if( !$this->id || is_numeric($this->id) )
        {
            $a = explode("/",$fname); $b = explode("/", __DIR__);
            while( count($a) && count($b) && $a[0] == $b[0] )
            {
                array_shift($a); array_shift($b);
            }
            $this->id = implode("/",$a);
            $this->injectVariables(cfg_getd('less','variables',[]));
        }
        return parent::compileFile($fname, $outFname);
    }
    
    /**
     * @internal Compiles LESS code
     */
    function compile($string, $name = null)
    {
        $dirs = (array)$this->importDir;
        foreach(array_reverse(cfg_getd('less','files',[])) as $f )
        {
            $dirs[] = dirname($f);
            $string = "@import \"". basename($f)."\"; $string";
            $this->debug("Added global LESS file '$f'");
        }
        $this->setImportDir(array_unique($dirs));
        try
        {
            return parent::compile($string, $name);
        }
        catch (\Exception $ex)
        {
            $this->error($ex->getMessage());
        }
        return "";
    }
    
    /**
     * @internal Overwritten to process variable injection
     */
    function getRegisteredVariable($name)
    {
        if( isset($this->injected[$name]) )
            return $this->injected[$name];
        if( isset($this->injected[str_replace("@","",$name)]) )
            return $this->injected[str_replace("@","",$name)];
        return false;
    }
    
    /**
     * @internal Overwritten to process variable injection
     */
    function injectVariables($args)
    {
        if( count($args)<1 )
            return;
        $this->injecting = true;
        parent::injectVariables($args);
        $this->injecting = false;
    }
    
    /**
     * @internal Overwritten to catch some errors
     */
    function get($name)
    {
        try
        {
            return parent::get($name);
        }
        catch(\Exception $ex)
        {
            $this->error($ex->getMessage());
        }
        return ["string","",[]];
    }
    
    /**
     * @internal Overwritten handle extended logic
     */
    function set($name, $value)
    {
        $reg = false; $log = true;
        if( $this->injecting )
        {
            $this->debug("Inject variable {$name} = ".json_encode($value));
            $this->injected[$name] = $value;
            $log = false;
        }
        else
            $reg = $this->getRegisteredVariable($name);
        
        if( $value[0] == "function" )
        {
            switch( $value[1] )
            {
                case 'register': 
                    $reg = $this->getRegisteredVariable($name);
                    if( !$reg )
                    {
                        $reg = parent::flattenList($value[2]); 
                        $this->injected[$name] = $reg;
                        $this->debug("Register variable {$name} = ".json_encode($reg));
                        $log = false;
                    }
                    break;
                case 'force': 
                    $overwritten = $this->getRegisteredVariable($name);
                    $reg = parent::flattenList($value[2]); 
                    $this->injected[$name] = $reg;
                    if( $overwritten )
                        $this->debug("Forced overwrite {$name} = ".json_encode($reg).", old value was ".json_encode($overwritten));
                    else
                        $this->debug("Forced variable {$name} = ".json_encode($reg));
                    $log = false;
                    break;
            }
        }
        if( $reg )
        {
            if( $log )
                $this->debug("Use variable {$name} = ". json_encode($reg).", ignored ".json_encode($value));
            parent::set($name, $reg);
        }
        else
        {
            //$this->debug("{$name} = ".json_encode($value));
            parent::set($name, $value);
        }
    }

    /**
     * @internal JSON serialization
     */
    public function jsonSerialize(): mixed
    {
        return ['LessCompiler'=>$this->id];
    }

}