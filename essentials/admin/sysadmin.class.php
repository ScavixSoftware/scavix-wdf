<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * @attribute[NoMinify]
 */
class SysAdmin extends HtmlPage
{
	var $PrefedinedCacheSearches = array('autoload_template','autoload_class',
		'lang_','method_','ref_attr_','resource_','filemtime_','doccomment_','DB_Cache_');
	
	function __initialize($title = "", $body_class = false)
    {
        global $CONFIG;
		
		unset($CONFIG["use_compiled_js"]);
		unset($CONFIG["use_compiled_css"]);
        
        if( current_event(true) != 'login'
            &&
            (   
                !isset($_SESSION['admin_handler_username']) 
                || !isset($_SESSION['admin_handler_password']) 
                || $_SESSION['admin_handler_username'] != $CONFIG['system']['admin']['username']
                || $_SESSION['admin_handler_password'] != $CONFIG['system']['admin']['password'] 
            ) )
            redirect('SysAdmin','Login');
        
        parent::__initialize($title, 'sysadmin');
        $this->_translate = false;
        
        if( current_event(true) != 'login' )
        {
            $nav = $this->addContent(new Control('div'));
            $nav->class = "navigation";
			
			foreach( $CONFIG['system']['admin']['actions'] as $label=>$def )
				$nav->content( new Anchor(buildQuery($def[0],$def[1]),$label) );
            $nav->content( new Anchor(buildQuery('SysAdmin','Cache'),'Cache') );
            $nav->content( new Anchor(buildQuery('SysAdmin','PhpInfo'),'PHP info') );
            $nav->content( new Anchor(buildQuery('SysAdmin','Testing'),'Testing') );
            $nav->content( new Anchor(buildQuery('SysAdmin','Logout'),'Logout') );
            $nav->content( new Anchor(buildQuery('',''),'Back to app') );
        }
    }
	
	function Index()
	{
		$this->addContent("<h1>Welcome,</h1>");
		$this->addContent("<p>please select an action on the top-right.</p>");
	}
	
    /**
     * @attribute[RequestParam('username','string',false)]
     * @attribute[RequestParam('password','string',false)]
     */
	function Login($username,$password)
	{
        global $CONFIG;
        
        if( $username===false || $password===false )
        {
            $this->AddContent(new SysAdminLogin());
            return;
        }
        
        if( $username != $CONFIG['system']['admin']['username'] || $password != $CONFIG['system']['admin']['password'] )
            redirect(get_class($this),'Login');
        
        $_SESSION['admin_handler_username'] = $username;
        $_SESSION['admin_handler_password'] = $password;
        redirect(get_class($this));
	}
    
    /**
     */
    function Logout()
    {
        unset($_SESSION['admin_handler_username']);
        unset($_SESSION['admin_handler_password']);
        redirect(get_class($this),'Login');
    }
	
	/**
	 * @attribute[RequestParam('search','string',false)]
	 * @attribute[RequestParam('show_info','bool',false)]
	 * @attribute[RequestParam('kind','string','Search key')]
     */
    function Cache($search,$show_info,$kind)
    {
		$this->addContent("<h1>Cache contents</h1>");
		
		$form = $this->addContent( new Form() );
		$form->AddText('search',$search);
		$form->AddSubmit('Search key')->name = 'kind';
		$form->AddSubmit('Search content')->name = 'kind';
		
		$form->content( '&nbsp;&nbsp;&nbsp;' );
		$form->content( new Anchor(buildQuery('SysAdmin','CacheClear'),'Clear the complete cache') );
		
		if( system_is_module_loaded('globalcache') )
		{
			$form->content( '&nbsp;&nbsp;' );
			$form->content( new Anchor(buildQuery('SysAdmin','Cache','show_info=1'),'Global cache info') );
		}
		
		$form->content( '<div><b>Predefined searches:</b><br/>' );
		foreach( $this->PrefedinedCacheSearches as $s )
		{
			$form->content( new Anchor(buildQuery('SysAdmin','Cache',"search=$s"),"$s") );
			$form->content( '&nbsp;' );
		}
		$form->content( '</div>' );
		
		if( !isset($_SESSION['admin_handler_last_cache_searches']) )
			$_SESSION['admin_handler_last_cache_searches'] = array();

		if( count($_SESSION['admin_handler_last_cache_searches']) > 0 )
		{
			$form->content( '<div><b>Last searches:</b><br/>' );
			foreach( $_SESSION['admin_handler_last_cache_searches'] as $s )
			{
				list($k,$s) = explode(":",$s);
				$form->content( new Anchor(buildQuery('SysAdmin','Cache',"search=$s".($k!='key'?'&kind=Search content':'')),"$k:$s") );
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
			
			$this->addContent("<br/>");
			$tabform = $this->addContent( new Form() );
			$tabform->action = buildQuery('SysAdmin','CacheDelMany');
			$tab = $tabform->content(new Table())->addClass('bordered');
			$tab->SetHeader('','key','action');
			$q = buildQuery('SysAdmin','CacheDel');
			foreach( cache_list_keys() as $key )
			{
				$found = ($kind=='Search content')
					?stripos( my_var_export(cache_get($key,"")), $search) !== false
					:stripos( $key, $search) !== false;
				if( $found )
				{
					$cb = new CheckBox('keys[]');
					$cb->value = $key;
					
					$del = new Anchor('','delete');					
					$del->onclick = "$.post('$q',{key:'".addslashes($key)."'},function(){ $('#{$del->id}').parents('tr').fadeOut(function(){ $(this).remove(); }); })";
					$tab->AddNewRow($cb,$key,$del);
				}
			}
			$footer = $tab->Footer()->NewCell();
			$footer->colspan = 2;
			$footer->content( new Anchor('','all') )->onclick = "$('#{$tab->id} .tbody input').attr('checked',true)";
			$footer->content('&nbsp;');
			$footer->content( new Anchor('','none') )->onclick = "$('#{$tab->id} .tbody input').removeAttr('checked')";
			
			$footer = $tab->Footer()->NewCell();
			$footer->content( new Anchor('','delete') )->onclick = "$('#{$tabform->id}').submit()";
		}
    }
	
	/**
	 * @attribute[RequestParam('key','string',false)]
     */
    function CacheDel($key)
	{
		cache_del($key);
		return AjaxResponse::None();
	}
	
	/**
	 * @attribute[RequestParam('keys','array',array())]
     */
	function CacheDelMany($keys)
	{
		foreach( $keys as $k )
			cache_del($k);
		redirect('SysAdmin','Cache');
	}
	
	/**
     */
	function CacheClear()
	{
		cache_clear();
		redirect('SysAdmin','Cache');
	}
	
	/**
	 * @attribute[RequestParam('extension','string',false)]
	 * @attribute[RequestParam('search','string',false)]
	 */
	function PhpInfo($extension,$search)
	{
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
		
		$tab = $this->addContent( Table::Make() );
		$tab->addClass('phpinfo')
			->SetCaption("Basic information")
			->AddNewRow("PHP version",phpversion())
			->AddNewRow("PHP ini file",php_ini_loaded_file())
			->AddNewRow("SAPI",php_sapi_name())
			->AddNewRow("OS",php_uname())
			->AddNewRow("Apache version",apache_get_version())
			->AddNewRow("Apache modules",implode(', ',apache_get_modules()))
			->AddNewRow("Loaded extensions",implode(', ',get_loaded_extensions()))
			->AddNewRow("Stream wrappers",implode(', ',stream_get_wrappers()))
			->AddNewRow("Stream transports",implode(', ',stream_get_transports()))
			->AddNewRow("Stream filters",implode(', ',stream_get_filters()))
			;
		
		$ext_nav = $this->addContent(new Control('div'))->css('margin-bottom','25px');
		$ext_nav->content("Select extension: ");
		$sel = $ext_nav->content(new Select());
		$ext_nav->content("&nbsp;&nbsp;&nbsp;Or search: ");
		$tb = $ext_nav->content(new TextInput());
		$tb->value = $search;
		
		$q = buildQuery('SysAdmin','PhpInfo');
		$sel->onchange = "wdf.redirect({extension:$(this).val()})";
		$tb->onkeydown = "if( event.which==13 ) wdf.redirect({search:$(this).val()})";
		
		$get_version = function($ext)
		{
			$res = ($ext=='zend')?zend_version():phpversion($ext);
			return $res?" [$res]":'';
		};
		
		$sel->SetCurrentValue($extension)->AddOption('','<select one>');
		$sel->AddOption('all','All values');
		foreach( array_keys($data) as $ext )
		{
			$ver = ($ext=='zend')?zend_version():phpversion($ext);
			$sel->AddOption($ext,$ext.$get_version($ext)." (".count($data[$ext]).")");
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
	 */
	function Testing()
	{
//		GoogleVisualization::$DefaultDatasource = model_datasource('system');
//		$chart = gvTable::Make("Unknown strings")
//			->setDbQuery('wdf_unknown_strings', "select term, hits")
//			->opt('width',500)
//			->opt('height',400)
//			->opt('pageSize',3)
//			->opt('page','enable');
//		$this->addContent($chart);
//		
//		$map = gMap::Make()
//			->css('width','500px')
//			->css('height','400px')
//			->AddMarker(-34.397, 150.644)
//			->AddMarkerTitled(-14.397, 150.644, 'Huhuhu')
//			->AddAddress("Rotdornweg 13a, 29389 Bad Bodenteich");
//		$this->addContent($map);
		
		$tab = $this->addContent( Table::Make() )
			->SetCaption('Muhaha')
			->SetHeader('H1','H2','H3')
			->SetFooter('hmmmm')
			->AddNewRow('eins','zwei','drei')
			->AddNewRow('zwei','drei','eins')
			->AddNewRow('drei','eins','zwei')
			->script("$('#{self}').click(function(){ $(this).overlay();});");
		
		$this->addContent( new Control('div') )
			->script("$('#{self}').click(function(){ $('#{$tab->id}').overlay('remove');});")
			->content('lorem lorem lorem lorem lorem lorem lorem lorem lorem ');
			
		$this->addContent( new Control('div') )
			->script("$('#{self}').click( function(){ wdf.post('sysaDmin/teStconfirm'); } );")
			->content('confirm');
	}
	
	/**
	 * 
	 */
	function testconfirm()
	{
		log_debug("testconfirm()",$_REQUEST);
		if( AjaxAction::IsConfirmed('CONFIRMATION') )
			return AjaxResponse::Error('Jop!');
		return AjaxAction::Confirm('CONFIRMATION', 'sysadmin', 'testconfirm');
	}
}