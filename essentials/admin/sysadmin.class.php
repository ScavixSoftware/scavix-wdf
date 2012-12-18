<?
 
class SysAdmin extends HtmlPage
{
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
}