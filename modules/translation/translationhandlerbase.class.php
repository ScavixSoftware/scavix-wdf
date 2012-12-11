<?
 
abstract class TranslationHandlerBase extends HtmlPage
{
	function __initialize($title = "", $body_class = false)
    {
        global $CONFIG;
        $cls = get_class($this);
        if( current_event(true) != 'login'
            &&
            (   
                !isset($_SESSION['translation_sync_username']) 
                || !isset($_SESSION['translation_sync_password']) 
                || $_SESSION['translation_sync_username'] != $CONFIG['translation']['sync']['username']
                || $_SESSION['translation_sync_password'] != $CONFIG['translation']['sync']['password'] 
            ) )
            redirect($cls,'Login');
        
        parent::__initialize($title, 'translationhandler');
        $this->_translate = false;
        
        if( current_event(true) != 'login' )
        {
            $nav = $this->addContent(new Control('div'));
            $nav->class = "navigation";
            $nav->content( new Anchor(buildQuery($cls,'NewStrings'),'New strings') );
            $nav->content( new Anchor(buildQuery($cls,'Fetch'),'Fetch strings') );
            $nav->content( new Anchor(buildQuery($cls,'Logout'),'Logout') );
            $nav->content( new Anchor(buildQuery('',''),'Back to app') );
        }
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
            $this->AddContent(new TranslationSyncLogin());
            return;
        }
        
        if( $username != $CONFIG['translation']['sync']['username'] || $password != $CONFIG['translation']['sync']['password'] )
            redirect(get_class($this),'Login');
        
        $_SESSION['translation_sync_username'] = $username;
        $_SESSION['translation_sync_password'] = $password;
        redirect(get_class($this));
	}
    
    /**
     */
    function Logout()
    {
        unset($_SESSION['translation_sync_username']);
        unset($_SESSION['translation_sync_password']);
        redirect(get_class($this),'Login');
    }
    
    /**
     */
    function NewStrings()
    {
        $this->addContent("<h1>New strings</h1>");
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        foreach( $ds->ExecuteSql("SELECT * FROM unknown_strings ORDER BY term") as $row )
        {
            $ns = new TranslationNewString($row['term'],$row['hits'],$row['last_hit']);
            $this->addContent($ns);
        }
    }
    
    /**
     * @attribute[RequestParam('term','string')]
     */
    function DeleteString($term)
    {
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        $ds->ExecuteSql("DELETE FROM unknown_strings WHERE term=?",$term);
        return 'ok';
    }
}