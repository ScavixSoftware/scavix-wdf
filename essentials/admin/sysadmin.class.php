<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Admin;

use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Base\Control;
use ScavixWDF\Base\HtmlPage;
use ScavixWDF\Base\Template;
use ScavixWDF\Controls\Anchor;
use ScavixWDF\Controls\Form\CheckBox;
use ScavixWDF\Controls\Form\Form;
use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Controls\Form\TextInput;
use ScavixWDF\Controls\Table\Table;
use ScavixWDF\Model\Model;

/**
 * ScavixWDF sysadmin page
 *
 * This is a tweak mechanism that allows you to manage your application.
 * For example you can create strings, manage the cache and check the PHP configuration.
 * @attribute[NoMinify]
 */
class SysAdmin extends HtmlPage
{
	public $PrefedinedCacheSearches = array('autoload_template','autoload_class',
		'lang_','method_','ref_attr_','resource_','filemtime_','doccomment_','DB_Cache_');

    protected $_contentdiv = false;
	protected $_subnav = false;
    protected $user = false;
    private $breadcrumbs = [];
    protected $pagetoolbar = false;

    function __construct($title = "", $body_class = false)
    {
        global $CONFIG;

		// sometimes state-/UI-less sites (like APIs) trickout the AJAX detection by setting this.
		// as we need UI this must be reset here
		unset($GLOBALS['result_of_system_is_ajax_call']);

		header("Content-Type: text/html; charset=utf-8"); // overwrite previously set header to ensure we deliver HTML
		unset($CONFIG["use_compiled_js"]);
		unset($CONFIG["use_compiled_css"]);

        $this->user = SysAdminUser::GetCurrent();
        $this->set('user', $this->user);
        $this->set('pagetoolbar', $this->pagetoolbar);
        if( $title )
			$this->set("page_title", $title);

        $_SESSION['wdf_translator_mode'] = (($this->user !== false) && (current_controller() == '\scavixwdf\translation\translationadmin') && (current_event() == 'translate'));
        if( current_event() != 'login' && !$this->user )
            redirect('sysadmin','login');

        parent::__construct("SysAdmin".($title ? " - $title" : ""), 'sysadmin');
        $this->_translate = false;
        $this->addJs('https://kit.fontawesome.com/5b6a078735.js');

        if( current_event() != 'login' )
        {
            $this->AddNavLink('home', 'Home', 'sysadmin', 'index');
            $subitems = [
                ['New Strings', 'translationadmin', 'newstrings'],
                ['Translate', 'translationadmin', 'translate'],
                ['Fetch', 'translationadmin', 'fetch'],
                ['Import', 'translationadmin', 'import'],
            ];
            $this->AddNavLink('language', 'Translations', $subitems);
            foreach($CONFIG['system']['admin']['actions'] as $l => $a)
                $this->AddNavLink((isset($a[3]) ? $a[3] : 'puzzle-piece'), $l, $a[0], $a[1]);
            $this->AddNavLink('hdd', 'Cache', 'sysadmin', 'cache');
            $this->AddNavLink('cogs', 'PHP info', 'sysadmin', 'phpinfo');
            $this->AddNavLink('database', 'Database', 'sysadmin', 'database');

//            $head = parent::content(new Control('div'));
//            $head->class = "header";
//            $nav = $head->content(new Control('div'));
//            $nav->class = "navigation";
//
//            $navdata = [];
//            $navdata['Home']          = ['sysadmin','index'];
//            $navdata['Translations']  = ['translationadmin','newstrings'];
//            $navdata = array_merge($navdata, $CONFIG['system']['admin']['actions']);
//            $navdata['Cache']         = ['sysadmin','cache'];
//            $navdata['PHP info']      = ['sysadmin','phpinfo'];
//            $navdata['Database']      = ['sysadmin','database'];
//
//            foreach( $navdata as $label=>$def )
//            {
//                if( !class_exists(fq_class_name($def[0])) )
//                    continue;
//                if( !$this->user->hasAccess($def[0],$def[1]) )
//                    continue;
//                $nav->content( new Anchor(buildQuery($def[0],$def[1]),$label) );
//            }
//
//            $nav->content(new Anchor(buildQuery('sysadmin','logout'),'Logout', 'logout'));
//            $nav->content(new Anchor(buildQuery('',''),gethostname(), 'logout'));
//			$this->_subnav = $head->content(new Control('div'));

            if( (current_event() == strtolower($CONFIG['system']['default_event'])) && !system_method_exists($this, current_event()) )
            {
                if( $this->user->hasAccess('sysadmin', 'index') )
                    redirect('sysadmin', 'index');
                else if( $this->user->hasAccess('translationadmin', 'newstrings') )
                    redirect('translationadmin', 'newstrings');
                else
                    die("Invalid user config");
            }
        }

//        $this->_contentdiv = parent::content(new Control('div'))->addClass('content');

//        $copylink = new Anchor('https://www.scavix.com', '&#169; 2012-'.date('Y').' Scavix&#174; Software GmbH &amp; Co. KG');
//        $copylink->target = '_blank';
//        $footer = parent::content(new Control('div'))->addClass('footer');
//		$footer->content("<br class='clearer'/>");
//        $footer->content($copylink);

        if( $this->user && !$this->user->hasAccess(current_controller(), current_event()) )
            redirect('sysadmin','forbidden');
    }

    /**
     * @internal SysAdmin forbidden page.
     */
    function Forbidden()
    {
        $this->content("<h1>Error!</h1>");
		$this->content("<p>You do not have permisison to use this feature.</p>");
    }

	/**
	 * @override Redirects contents to inner content div
	 */
//	function content($content)
//	{
//		return $this->_contentdiv->content($content);
//	}

	protected function subnav($label,$controller,$method,$data=[])
	{
		if( $this->user && $this->user->hasAccess($controller,$method) )
		{
            $tb = $this->addToolbar();
			$tb->content( new \ScavixWDF\Controls\Form\Button($label, $controller,$method,$data) );
		}
	}

	/**
	 * @internal SysAdmin index page.
	 */
	function Index()
	{
        $this->setTitle('Home');
		$this->content("<h1>Welcome ".$this->user->username.",</h1>");
		$this->content("<p>please select an action from the menu.</p>");
	}

    /**
	 * @internal SysAdmin login page.
     * @attribute[RequestParam('username','string','')]
     * @attribute[RequestParam('password','string','')]
     */
	function Login($username,$password)
	{
        if( !$username || !$password )
        {
            $this->content(Template::Make('sysadminlogin'));
            return;
        }

        if( SysAdminUser::Login($username,$password) )
            redirect(get_class_simple($this));
        else
            redirect(get_class_simple($this),'login');


	}

    /**
	 * @internal SysAdmin logout event.
     */
    function Logout()
    {
        if( $this->user )
            $this->user->Logout();
        redirect(get_class_simple($this),'login');
    }

	/**
	 * @internal SysAdmin cache manager.
	 * @attribute[RequestParam('search','string',false)]
	 * @attribute[RequestParam('show_info','bool',false)]
	 * @attribute[RequestParam('kind','string','Search key')]
     */
    function Cache($search,$show_info,$kind)
    {
		$this->setTitle('Cache contents');

		$form = $this->content( new Form() );
		$form->AddText('search',$search);
		$form->AddSubmit('Search key')->name = 'kind';
		$form->AddSubmit('Search content')->name = 'kind';

		$form->content( '&nbsp;&nbsp;&nbsp;' );
		$form->content( new Anchor(buildQuery('sysadmin','cacheclear'),'Clear the complete cache') );

		if( system_is_module_loaded('globalcache') )
		{
			$form->content( '&nbsp;&nbsp;' );
			$form->content( new Anchor(buildQuery('sysadmin','cache','show_info=1'),'Global cache info') );
		}

		$form->content( '<div><b>Predefined searches:</b><br/>' );
		foreach( $this->PrefedinedCacheSearches as $s )
		{
			$form->content( new Anchor(buildQuery('sysadmin','cache',"search=$s"),"$s") );
			$form->content( '&nbsp;' );
		}
		$form->content( '</div>' );

		if( !isset($_SESSION['admin_handler_last_cache_searches']) )
			$_SESSION['admin_handler_last_cache_searches'] = [];

		if( count($_SESSION['admin_handler_last_cache_searches']) > 0 )
		{
			$form->content( '<div><b>Last searches:</b><br/>' );
			foreach( $_SESSION['admin_handler_last_cache_searches'] as $s )
			{
				list($k,$s) = explode(":",$s);
				$form->content( new Anchor(buildQuery('sysadmin','cache',"search=$s".($k!='key'?'&kind=Search content':'')),"$k:$s") );
				$form->content( '&nbsp;' );
			}
			$form->content( '</div>' );
		}

		if( $show_info && system_is_module_loaded('globalcache') )
			$form->content( "<pre>".globalcache_info()."</pre>" );

		if( $search )
		{
			if( !in_array($search,$this->PrefedinedCacheSearches) )
			{
				$_SESSION['admin_handler_last_cache_searches'][] = ($kind=='Search content')?"content:$search":"key:$search";
				$_SESSION['admin_handler_last_cache_searches'] = array_unique($_SESSION['admin_handler_last_cache_searches']);
			}

			$this->content("<br/>");
			$tabform = $this->content( new Form() );
			$tabform->action = buildQuery('sysadmin','cachedelmany');
			$tab = $tabform->content(new Table())->addClass('bordered');
			$tab->SetHeader('','key','action');
			$q = buildQuery('sysadmin','cachedel');
			foreach( cache_list_keys() as $key )
			{
				$found = ($kind=='Search content')
					?stripos( render_var(cache_get($key,"")), $search) !== false
					:stripos( $key, $search) !== false;
				if( $found )
				{
					$cb = new CheckBox('keys[]');
					$cb->value = $key;

					$del = new Anchor('','delete');
					$del->onclick = "$.post('$q',{key:'".addslashes($key)."'},function(){ $('#{$del->id}').parents('.tr').fadeOut(function(){ $(this).remove(); }); })";
					$tab->AddNewRow($cb,$key,$del);
				}
			}
			$footer = $tab->Footer()->NewCell();
			$footer->content( new Anchor('','all') )->onclick = "$('#{$tab->id} .tbody input').prop('checked',true);";
			$footer->content('&nbsp;');
			$footer->content( new Anchor('','none') )->onclick = "$('#{$tab->id} .tbody input').prop('checked',false)";

			$footer = $tab->Footer()->NewCell();
			$footer->content( new Anchor('','delete') )->onclick = "$('#{$tabform->id}').submit()";

            $tab->Footer()->NewCell();
		}
    }

	/**
	 * @internal SysAdmin cache manager: delete event.
	 * @attribute[RequestParam('key','string',false)]
     */
    function CacheDel($key)
	{
		cache_del($key);
		return AjaxResponse::None();
	}

	/**
	 * @internal SysAdmin cache manager: delete many event.
	 * @attribute[RequestParam('keys','array',array())]
     */
	function CacheDelMany($keys)
	{
		foreach( $keys as $k )
			cache_del($k);
		redirect('sysadmin','cache');
	}

	/**
	 * @internal SysAdmin cache manager: clear event.
     */
	function CacheClear()
	{
		cache_clear();
		redirect('sysadmin','cache');
	}

	/**
	 * @internal SysAdmin phpinfo.
	 * @attribute[RequestParam('extension','string',false)]
	 * @attribute[RequestParam('search','string',false)]
	 * @attribute[RequestParam('dump_server','bool',false)]
	 */
	function PhpInfo($extension,$search,$dump_server)
	{
        $this->setTitle('PHP info');
		if( $dump_server )
			$search = $extension = "";
		if( $search )
			$extension = null;

		foreach( ini_get_all() as $k=>$v)
		{
			$k = explode('.',$k,2);
			if( count($k)<2 )
				$k = array('Core',$k[0]);

			$data[$k[0]][$k[1]] = $v;
		}
		ksort($data);

		$tab = $this->content( Table::Make() );
		$tab->addClass('phpinfo')
			->SetCaption("Basic information")
			->AddNewRow("PHP version",phpversion())
			->AddNewRow("PHP ini file",php_ini_loaded_file())
			->AddNewRow("SAPI",PHP_SAPI)
			->AddNewRow("OS",php_uname());
        if(function_exists('apache_get_version'))
			$tab->AddNewRow("Apache version",apache_get_version());
        if(function_exists('apache_get_modules'))
            $tab->AddNewRow("Apache modules",implode(', ',apache_get_modules()));
        if(function_exists('get_loaded_extensions'))
            $tab->AddNewRow("Loaded extensions",implode(', ',get_loaded_extensions()));
        if(function_exists('stream_get_wrappers'))
            $tab->AddNewRow("Stream wrappers",implode(', ',stream_get_wrappers()));
        if(function_exists('stream_get_transports'))
            $tab->AddNewRow("Stream transports",implode(', ',stream_get_transports()));
        if(function_exists('stream_get_filters'))
            $tab->AddNewRow("Stream filters",implode(', ',stream_get_filters()));

		$ext_nav = $this->content(new Control('div'))->css('margin-bottom','25px');
		$ext_nav->content("Select extension: ");
		$sel = $ext_nav->content(new Select());
		$ext_nav->content("&nbsp;&nbsp;&nbsp;Or search: ");
		$tb = $ext_nav->content(new TextInput());
		$tb->value = $search;

		$q = buildQuery('sysadmin','phpinfo');
		$sel->onchange = "wdf.redirect({extension:$(this).val()})";
		$tb->onkeydown = "if( event.which==13 ) wdf.redirect({search:$(this).val()})";

		$ext_nav->content('&nbsp;&nbsp;&nbsp;Or ');
		$q = buildQuery('sysadmin','phpinfo','dump_server=1');
		$ext_nav->content( new Anchor($q,'dump the $_SERVER variable') );

		$get_version = function($ext)
		{
			$res = ($ext=='zend')?zend_version():phpversion($ext);
			return $res?" [$res]":'';
		};

		$sel->setValue($extension)->AddOption('','(select one)');
		$sel->AddOption('all','All values');
		foreach( array_keys($data) as $ext )
		{
			$ver = ($ext=='zend')?zend_version():phpversion($ext);
			$sel->AddOption($ext,$ext.$get_version($ext)." (".count($data[$ext]).")");
		}

		if( $dump_server )
		{
			$tab = $this->content( new Table() )
				->addClass('phpinfo')
				->SetCaption('Contents of the $_SERVER variable')
				->SetHeader('Name','Value');
			foreach( $_SERVER as $k=>$v )
				$tab->AddNewRow($k,$v);
		}
		if( $extension || $extension == 'all' || $search )
		{
			foreach( $data as $k=>$config )
			{
				if( !$search && $k != $extension && $extension != 'all' )
					continue;

				$tab = false;
				foreach( $config as $ck=>$v )
				{
					if( $search && stripos($ck,$search)===false && stripos($v['local_value'],$search)===false && stripos($v['global_value'],$search)===false )
						continue;

					if( !$tab )
					{
						$tab = $this->content( new Table() )
							->addClass('phpinfo')
							->SetCaption($k.$get_version($k))
							->SetHeader('Name','Local','Master');
					}
					$tr = $tab->NewRow(array($ck,$v['local_value'],$v['global_value']));
					if( $v['local_value']!=='' && $v['local_value'] != $v['global_value'] )
						$tr->GetCell(2)->css('color','red');
				}
			}
		}
	}

	/**
	 * @internal SysAdmin database info.
	 * @attribute[RequestParam('name','string',false)]
	 * @attribute[RequestParam('table','string',false)]
	 */
	function Database($name,$table)
	{
        $this->setTitle('Database');

        if( !$name )
        {
            $this->content("<h2>Please select a database:</h2>");
            foreach( $GLOBALS['CONFIG']['model'] as $alias=>$cfg )
                \ScavixWDF\Controls\Form\Button::Textual($alias)->LinkTo('sysadmin','database',['name'=>$alias])->appendTo($this);
            return;
        }

        $this->content("<h1>Database '$name'</h1>");

        $versioning_mode = ifavail($_SESSION,'sysadmin_sql_versioning') == '1';
        if( $versioning_mode == '1' )
            \ScavixWDF\Controls\Form\Button::Textual("Show plain SQL create statements","wdf.controller.get('togglesqlmode',{on:0})")
                ->appendTo($this);
        else
            \ScavixWDF\Controls\Form\Button::Textual("Show versioning-prepared create statements","wdf.controller.get('togglesqlmode',{on:1})")
                ->appendTo($this);

        $this->content("<h2>Tables</h2>");
        $ds = model_datasource($name);
        foreach( $ds->Driver->listTables() as $tab )
            \ScavixWDF\Controls\Form\Button::Textual($tab)->LinkTo('sysadmin','database',['name'=>$name,'table'=>$tab])
                ->appendTo($this);

        if( !$table )
            return;

        $this->content("<h2>Table '$table' (".($versioning_mode?'for versioning':'plain').")</h2>");
        $schema = $ds->Driver->getTableSchema($table);
        //log_debug($schema);
        $create = $schema->CreateCode;
        if( $versioning_mode )
        {
            $create = preg_replace('/\sAUTO_INCREMENT=\d+/i','',$create);
            $create = preg_replace('/\sCOLLATE\s[^\s]+\s/i',' ',$create);
            $create = preg_replace('/\sENGINE=[^\s]+\s/i',' ',$create);
            $create = preg_replace('/\sDEFAULT\sCHARSET=[^\s]+\s/i',' ',$create);
            $create = preg_replace('/\sCOLLATE=[^\s]+(\s*)/i','$1',$create);
            $create = preg_replace('/\sUSING\sBTREE/i','',$create);

            $create .= ";";

            $create = preg_replace('/CREATE\sALGORITHM.*VIEW/i',"CREATE OR REPLACE VIEW",$create);
            $create = preg_replace('/CREATE TABLE `/i','CREATE TABLE IF NOT EXISTS `',$create);

            if( stripos($create,"CREATE OR REPLACE VIEW") !== false )
            {
                $create = preg_replace('/(\sAS)\s+(SELECT\s)/i',"$1\n$2",$create);
                $create = preg_replace('/(,.+\s+AS\s+[^,]+)/iU',"\n\t$1",$create);
                $create = preg_replace('/\s(FROM\s)/i',"\n$1",$create);
                $create = preg_replace('/\s(LEFT JOIN\s)/i',"\n$1",$create);
                $create = preg_replace('/\s(WHERE\s)/i',"\n$1",$create);
                $create = preg_replace('/\s(GROUP BY\s)/i',"\n$1",$create);

                $create .= "\n\n<b style='color:red'>NOTE THAT THIS IS A VIEW AND IT SHOULD BE UPDATED FROM SOURCE BECAUSE OF * REFERENCES</b>";
            }
            else
            {
                if( defined("DATABASE_VERSION") && defined("DATABASE_FOLDER") )
                {
					$dbf = constant("DATABASE_FOLDER");
                    foreach( system_glob_rec($dbf,"{$table}.sql") as $file )
                    {
                        $current = file_get_contents($file);
                        $fn = str_replace($dbf,'',$file);
                        if( preg_replace('/\s/','',$current) == preg_replace('/\s/','',$create) )
                            $this->append("<b style='color:green'>Definition file '$fn' is up to date</b>");
                        else
                        {
                            $this->append("<b style='color:red'>Definition file '$fn' differs. Make sure the update path contains all ALTERs.</b>");

                            if( shell_exec("which diff") )
                            {
                                $test = system_app_temp_dir('sysadmin_db')."$table.create.sql";
                                file_put_contents($test, "$create\n");
                                $diff = shell_exec("diff -iEZbB \"$file\" \"$test\"");
                                $this->content("<div>DIFF:</div><pre style='border: 1px solid black'>$diff</pre>");
                                unlink($test);
                            }
                            else
                            {
                                $this->content("<pre id='table-current' style='opacity:0.6; color: red; position:absolute;'>$current</pre>");
                                $this->addDocReady("$('#table-current').position({my:'left top',at:'left top',of:'#table-code'});");
                            }
                        }
                        break;
                    }
                }
            }
        }
        $this->content("<pre id='table-code'>$create</pre><br/><br/>");

        $properties = ["/**"];
        $fields = [];
        foreach( $schema->Columns as $col )
        {
            switch( $col->Type )
            {
                case 'char':
                case 'varchar':
                case 'text':
                case 'mediumtext':
                case 'longtext':
                case 'tinytext':
                case 'enum':
                case 'set':
                    $type = "string";
                    break;
                case 'timestamp':
                case 'datetime':
                case 'date':
                case 'time':
                    $type = "\ScavixWDF\Base\DateTimeEx|string";
                    break;
                case 'tinyint':
                case 'bigint':
                    $type = "int";
                    break;
                default:
                    $type = $col->Type;
                    break;
            }
            $valid_name = preg_replace('/[^a-zA-Z0-9_]/','_',$col->Name);
            if( preg_match('/^[0-9]/',$valid_name) )
                $valid_name = "_$valid_name";
            $properties[] = " * @property {$type} ".'$'."{$col->Name}";
            $fields[] = "/** @var {$type} */";
            $fields[] = 'public $'.$valid_name.';';
            if( $valid_name != $col->Name )
            {
                $fields[] = "// Note: Real name is '{$col->Name}', but that contains characters that are not allowed in PHP property names.";
            }
            $fields[] = '';
        }
        $properties[] = " */";

        $cls = str_replace(' ','',ucwords(str_replace('_', ' ', rtrim($table,'s'))));
        //perhaps we'll have a better mapping later
        // $cls = str_replace("Model", "", Model::TryGetClassFromTablename($schema->Name));

        $properties = implode("\n",$properties);
        $fields = implode("\n\t",$fields);
        $code = <<<EOT
            trait {$cls}Schema
            {
            \t$fields
            \tfunction GetTableName():string { return '{$schema->Name}'; }
            }
            EOT;
        $this->content("<pre>$code</pre><br/><br/>");

        $listing = \ScavixWDF\JQueryUI\uiDatabaseTable::Make($ds,false,$table)
            ->AddPager()
            ->appendTo($this);
        $listing->PagerAtTop = true;
	}

    /**
	 * @internal SysAdmin toggle database info mode.
	 * @attribute[RequestParam('on','bool',false)]
	 */
    function ToggleSqlMode($on)
    {
        $_SESSION['sysadmin_sql_versioning'] = $on?1:0;
        return AjaxResponse::Reload();
    }


	protected function AddBreadcrumb($caption, $url = false)
	{
		if( !isset($this->breadcrumbs[$caption]) )
			$this->breadcrumbs[$caption] = array('caption' => $caption, 'url' => $url);
	}

    protected function GenerateBreadcrumbNavigation()
	{
		$ret = '';
		if(count($this->breadcrumbs) > 1)
		{
			$i = 0;
			$ret .= '<span class="breadcrumbnavbar no-print">';
			foreach($this->breadcrumbs as $index => $bcrumb)
			{
				if($i++ >= count($this->breadcrumbs)-1)
					break;
				if($i > 1)
					$ret .= '&nbsp;/&nbsp;';
				if($bcrumb['url'] && ($bcrumb['caption'] != ''))
					$ret .= '<a href="'.$bcrumb['url'].'">'.$bcrumb['caption'].'</a>';
				else
					$ret .= $bcrumb['caption'];
			}
			$ret .= '</span>';
            if($ret == '<span class="breadcrumbnavbar no-print">&nbsp;/&nbsp;</span>')
                $ret = '';
		}
		return $ret;
	}

	protected function AddNavLink($icon, $label, $page, $event = false)
	{
        if($event)
            $event = strtolower($event);
		if(is_array($page))
		{
			$u = $this->admin;
			if( 0 == intval(implode("",array_map(function($e)use($u){ return $this->user->hasAccess($e[1], $e[2])?1:0; },$page))) )
				return;
			// it has a submenu
			$link = new Anchor('javascript:void(0)', "");
		}
        elseif(starts_with($page, 'http') || starts_with($page, '/'))
        {
            $link = new Anchor($page, '', '', ($event ? $event : ''));
        }
		else
		{
            if(!$this->user->hasAccess($page, $event))
                return;
            $link = new Anchor(buildQuery($page, $event), "");
		}
		$link->content('<i class="fas fa-'.$icon.' fa-fw"></i>');
		$link->content("<span class='label'>$label</span>");

		$li = Control::Make('li');
		$li->content($link);

		if(is_array($page))
		{
			$iscurrent = false;
            foreach($page as $subitem)
			{
                if(!$this->user->hasAccess($subitem[1], isset($subitem[2]) ? $subitem[2] : ''))
                    continue;
                $li->addClass('hassubmenu');
				// set main menu item as current
                if((current_controller(true) == strtolower($subitem[1])) || ends_iwith(current_controller(true), '\\'.$subitem[1]))
                {
					$li->addClass('current');
					$li->addClass('focused');
                    $iscurrent = true;
                }
			}

			// it has a submenu
			$link->content("<span class='arrow'><i class='fas fa-angle-".($iscurrent ? 'down' : 'right')."'></i></span>");
			$ul = Control::Make('ul')->addClass('dropdown');
            if($iscurrent)
                $ul->addClass('open');
			foreach($page as $subitem)
			{
                if(!$this->user->hasAccess($subitem[1], isset($subitem[2]) ? $subitem[2] : ''))
                    continue;
				$subli = Control::Make('li');
				$sublink = new Anchor(buildQuery($subitem[1], isset($subitem[2]) ? $subitem[2] : ''), "");
				$sublink->content("<span class='label'>&nbsp;&nbsp;".$subitem[0]."</span>");
				$subli->content($sublink);

                // set main menu item as current
                if(((current_controller(true) == strtolower($subitem[1])) || ends_iwith(current_controller(true), '\\'.$subitem[1])) && ((isset($subitem[2]) ? $subitem[2] : 'init') == current_event()))
					$subli->addClass('current');
				$ul->content($subli);
			}
			$li->content($ul);
		}
        else
        {
            if((current_controller(true) == strtolower($page)) || ends_iwith(current_controller(true), '\\'.$page))
            {
                if(!$event)
                    $li->addClass('current');
                elseif($event == current_event())
                    $li->addClass('current');
            }
        }

		$this->add2var('navlinks', $li);
		return $this;
	}

    protected function addToolbar()
    {
        if(!$this->pagetoolbar)
        {
            $this->pagetoolbar = Control::Make('div')->addClass('pagetoolbar'); //->appendTo($this);
            $this->set('pagetoolbar', $this->pagetoolbar);
        }
        return $this->pagetoolbar;
    }

    protected function setTitle($title)
    {
        $this->set("page_title",$title);
        return $this;
    }
}
