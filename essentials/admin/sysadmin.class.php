<?
 
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
			$footer->content( new Anchor('','all') )->onclick = "$('#{$tab->id} tbody input').attr('checked',true)";
			$footer->content('&nbsp;');
			$footer->content( new Anchor('','none') )->onclick = "$('#{$tab->id} tbody input').removeAttr('checked')";
			
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
		return " ";
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
	 */
	function Testing()
	{
		GoogleVisualization::$DefaultDatasource = model_datasource('system');
		$chart = gvTable::Make("Unknown strings")
			->setDbQuery('wdf_unknown_strings', "select term, hits")
			->opt('width',500)
			->opt('height',400)
			->opt('pageSize',3)
			->opt('page','enable');
		$this->addContent($chart);
	}
}