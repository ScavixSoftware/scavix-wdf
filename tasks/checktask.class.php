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
 * @internal CLI only Task run `php index.php check` for info
 */
class CheckTask extends Task
{
    function Run($args)
    {
        $status = [];
        $status["short_open_tag"] = is_in(ini_get('short_open_tag'),'on','On','1','true','True','TRUE','ON','oN')
            ?'ok':"Needs to be enabled";
        $status["display_errors"] = !is_in(ini_get('display_errors'),'on','On','1','true','True','TRUE','ON','oN')
            ?'ok':"Should be disabled";
        
        $this->write("Settings",$status);
        
        $status = [];
        $status["php-curl"]    = function_exists('curl_init')?'ok':"CURL is missing, required for some features";
        $status["php-xml"]     = function_exists('utf8_encode')?'ok':"XML is missing, required for some features";
        $status["php-sqlite3"] = extension_loaded('pdo_sqlite')?'ok':"(optional) SQLite driver is missing";
        $status["php-mysql"]   = extension_loaded('pdo_mysql')?'ok':"(optional) MySQL driver is missing";
        
        $this->write("Dependencies",$status);
    }
    
    private function write($title, $status)
    {
        $log = logging_get_logger('cli');
        $log->debug("$title:");
        foreach( $status as $n=>$v )
            if( $v == 'ok' )
                $log->debug(" * ".str_pad("$n",20),$v);
            else
                $log->warn(" * ".str_pad("$n",20),$v);
    }
    
    function Strings($args)
    {
        $dir = realpath(ifavail($args,'dir'));
        if( !$dir )
            return log_info("Syntax: check-strings dir=<base-folder>");
        
        translation_known_constants(); // just to init everything
        
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        $ids = $ds->ExecuteSql("SELECT DISTINCT id FROM wdf_translations")->Enumerate('id');
        
        $multifind = function($id,$content,$replace,$prefix,$suffix=['"',"'"])
        {
            foreach( $suffix as $s )
            {
                if( $replace )
                {
                    if( strpos($content,str_replace($replace,$prefix.$s,$id).$s) !== false ) 
                        return true;
                }
                else
                {
                    if( strpos($content,$prefix.$s.$id.$s) !== false ) 
                        return true;
                }
            }
            return false;
        };
        $processfile = function($file)use(&$ids,$multifind)
        {
            $c = file_get_contents($file);
            $used = [];
            foreach( $ids as $i )
            {
                if( strpos($c,$i) !== false )
                {
                    $used[] = $i;
                    continue;
                }
                $pre = array_first(explode("_",$i));
                if( is_in($pre,'TITLE','TXT') )
                {
                    if( $multifind($i,$c,"{$pre}_","::Confirm(") )
                    {
                        $used[] = $i;
                        log_debug("Found DLG $i");
                        continue;
                    }
                }
            }
            $ids = array_diff($ids,$used);
        };
        
        log_debug("Preprocessing");
        
        $used = [];
        foreach( $ids as $i )
        {
            if( strpos("$i","TXT_COUNTRY_") === 0 )
                $used[] = $i;
            elseif( strpos("$i","TXT_PAYMENTPROVIDER_") === 0 )
                $used[] = $i;
        }
        $ids = array_diff($ids,$used);
        log_debug("...found ".count($used)." IDs",$used);
        
        log_debug("Processing translation recursion");
        $used = [];
        foreach( $GLOBALS['translation']['strings'] as $id=>$value )
        foreach( $ids as $i )
            if( strpos($value,$i) )
                $used[] = $i;
        $ids = array_diff($ids,$used);
        log_debug("...found ".count($used)." IDs",$used);
        
        $datapath = realpath($GLOBALS['CONFIG']['translation']['data_path']);
        log_debug("Processing files in folder $dir");
        foreach( ['*.php','*.js'] as $pattern )
        foreach( system_glob_rec($dir, $pattern) as $file )
        {
            if( 0 === stripos(realpath($file), $datapath) )
                continue;
            $processfile($file);
            if( count($ids)==0 )
                break 2;
        }
        
        log_debug("Unused",array_values($ids));
        
        if( avail($args,'remove') || in_array('remove',$args) )
        {
            foreach( $ids as $i )
            {
                $ds->ExecuteSql("DELETE FROM wdf_translations WHERE id=?",$i);
                log_debug("Removed $i");
            }
        }
    }
    
    private function pureCode($file,&$contains_at)
    {
        $res = '';
        $commentTokens = array(T_COMMENT);
        if (defined('T_DOC_COMMENT'))
            $commentTokens[] = T_DOC_COMMENT; // PHP 5
        if (defined('T_ML_COMMENT'))
            $commentTokens[] = T_ML_COMMENT;  // PHP 4

        $cnts = $cntd = 0;
        foreach( token_get_all(file_get_contents($file)) as $token )
        {    
            if(is_array($token))
            {
                if (in_array($token[0], $commentTokens))
                    continue;
                $token = $token[1];
            }
            if( $token == "'" )
                $cnts++;
            elseif( $token == '"' )
                $cntd++;
            elseif( $token == '@' && ($cnts%2) == 0 && ($cntd%2) == 0 )
                $contains_at = true;
            
            $res .= $token;
        }
        return $res;
    }
    
    function php8($args)
    {
        $dir = realpath(ifavail($args,'dir'));
        if( !$dir )
            return log_info("Syntax: check-php8 dir=<base-folder> [ignore=<wdf-folder>]");
        
        $removed_functions = [
            // according to https://www.php.net/manual/de/migration80.incompatible.php
            'create_function','imap_header',
            'hebrevc','convert_cyr_string','money_format','ezmlm_hash','restore_include_path',
            'get_magic_quotes_gpc','get_magic_quotes_runtime','fgetss',
            // internal
            '__initialize',
        ];
        $res_to_obj = [
            // according to https://www.php.net/manual/de/migration80.incompatible.php
            'curl_init','curl_multi_init','curl_share_init','enchant_broker_init','enchant_broker_request_dict','enchant_broker_request_pwl_dict',
            'imagecreate','openssl_x509_read','openssl_csr_sign','openssl_csr_new','openssl_pkey_new','shmop_open',
            'socket_create','socket_create_listen','socket_accept','socket_import_stream','socket_addrinfo_connect','socket_addrinfo_bind',
            'socket_wsaprotocol_info_import','socket_addrinfo_lookup','msg_get_queue','sem_get','shm_attach','xml_parser_create',
            'xml_parser_create_ns','xmlwriter_','inflate_init','deflate_init'
        ];
        $removed_classes = [
            // according to https://www.php.net/manual/de/migration80.incompatible.php
            'DOMNameList', 'DomImplementationList', 'DOMConfiguration', 'DomError', 'DomErrorHandler',
            'DOMImplementationSource', 'DOMLocator', 'DOMUserDataHandler', 'DOMTypeInfo', 
            // removed from WDF, see #SI-18
            'PHPExcel','InvoicePdf','PdfDocument'
        ];
        $removed_modules = [
            // removed from WDF, see #SI-18
            'mod_phpexcel', 'pear', 'zend', 'invoices',
        ];
        
        $ignores = array_filter(array_map(
            'realpath', 
            ifavail($args,'ignore')? force_array(ifavail($args,'ignore')):[]
        ));
        $ignores[] = __SCAVIXWDF__;
        
        foreach( system_glob_rec($dir,"*.php") as $file )
        {
            foreach( $ignores as $ign )
                if( 0 === stripos(realpath($file), $ign) )
                    continue(2);
            if( stripos($file,'/vendor/') )
                continue;
            
            $relfile = trim(str_replace($dir,"", $file),"/");
            $findings = [];
            $contains_at = false;
            $code = $this->pureCode($file,$contains_at);
            
            if( $contains_at )
                $findings[] = "(I) The @ operator will no longer silence fatal errors";
            if( strpos($code,"call_user_func_array") !== false )
                $findings[] = "(I) 'call_user_func_array': array keys will now be interpreted as parameter names, instead of being silently ignored";
            if( strpos($code, "final private function") !== false )
                $findings[] = "(W) Private methods cannot be final as they are never overridden by other classes";
            
            if( strpos($code,"#[") !== false && strpos($code,"preg_") === false )
                $findings[] = "(F) Comment is now interpreted as attribute: '#[' ";
            
            if( strpos($code,"function __autoload(") !== false )
                $findings[] = "(F) The ability to specify an autoloader using an __autoload() function has been removed";
            
            if( preg_match('/([^a-z0-9-_\.]each[\(\s])/i', $code, $m) !== false && count($m) )
                $findings[] = "(F) 'each()' has been removed, foreach or ArrayIterator should be used instead";
            
            foreach( ['func_get_args(','func_get_arg(','func_num_args('] as $f )
                if( strpos($code, $f) !== false )
                    $findings[] = "(I) Deprecation found, use '...\$args'-Syntax instead of '$f'";
            
            foreach( $removed_functions as $f )
                if( strpos($code, $f) !== false )
                    $findings[] = "(F) Removed function found: '$f'";
                
            foreach( $removed_classes as $c )
                if( strpos($code, $c) !== false )
                    $findings[] = "(F) Removed class found: '$c'";
                
            foreach( $removed_modules as $m )
                if( preg_match("/system_load_module.*{$m}/",$code) || preg_match("/modules[^;]+{$m}/m",$code) )
                    $findings[] = "(F) Removed module found: '$m'";
            
            if( strpos($code,"is_resource") !== false )
                foreach( $res_to_obj as $rto )
                    if( strpos($code, $rto) !== false )
                        $findings[] = "(E) '$rto': Several Ressources have been migrated to Objekts, return value checks using is_resource() should be replaced with checks for false";
                            
            if( strpos($code,"mktime()") ) 
                $findings[] = "(E) 'mktime()' now require at least one argument";
            if( strpos($code,"gmmktime()") ) 
                $findings[] = "(E) 'gmmktime()' now require at least one argument";
            
            if( count($findings) )
            {
                $findings = implode("\n  - ",$findings);
                log_info("$file\n  - $findings");
            }
        }
    }
}
