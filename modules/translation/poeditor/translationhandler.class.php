<?
 
class TranslationHandler extends TranslationHandlerBase
{
    function __initialize($title = "", $body_class = false)
    {
        parent::__initialize($title, $body_class);
        if( !isset($GLOBALS['CONFIG']['translation']['sync']['poeditor_api_key']) || !$GLOBALS['CONFIG']['translation']['sync']['poeditor_api_key'] )
            system_die("POEditor API key missing!");
        if( !isset($GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id']) || !$GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id'] )
            system_die("POEditor ProjectID missing!");
    }
    
    private function request($data=array())
    {
        $data['api_token'] = $GLOBALS['CONFIG']['translation']['sync']['poeditor_api_key'];
        $data['id'] = $GLOBALS['CONFIG']['translation']['sync']['poeditor_project_id'];
        //log_debug($data);
        $res = sendHTTPRequest('http://poeditor.com/api/',$data);
        $res = json_decode($res);
        if( $res->response->code != 200 )
        {
            log_error("POEditor API returned error: ".$res->response->message,"Details: ",$res);
            return false;
        }
        return $res;
    }
    
    private function fetchTerms($lang_code,$defaults = false)
    {
        $response = $this->request(array('action'=>'view_terms','language'=>$lang_code));
        $res = array();
        foreach( $response->list as $lang )
        {
            $res[$lang->term] = isset($lang->definition)?$lang->definition->form:'';
            if( !$res[$lang->term] && $defaults )
                $res[$lang->term] = $defaults[$lang->term];
        }
        return $res;
    }
    
    /**
     * @attribute[RequestParam('languages','array',false)]
     */
    function Fetch($languages)
    {
        global $CONFIG;
        $this->addContent("<h1>Fetch strings</h1>");
        $response = $this->request(array('action'=>'list_languages'));
        
        if( !$languages )
        {
            $div = $this->addContent(new Form());
            foreach( $response->list as $lang )
            {
                $cb = $div->content( new CheckBox('languages[]') );
                $cb->value = $lang->code;
                $div->content($cb->CreateLabel($lang->name." ({$lang->code}, {$lang->percentage}% complete)"));
                $div->content("<br/>");
            }
            $a = $div->content(new Anchor('#','Select all'));
            $a->script("$('#{$a->id}').click(function(){ $('input','#{$div->id}').attr('checked',true); });");
            $div->content("&nbsp;&nbsp;");
            $div->AddSubmit("Fetch");
            return;
        }
        
        $head = array();
        foreach( $response->list as $lang )
            $head[$lang->code] = array('percentage_complete'=>$lang->percentage/100, 'percentage_empty'=>(1-$lang->percentage/100), 'syntax_error_qty'=>0);
        $info = "\$GLOBALS['translation']['properties'] = ".var_export($head,true);
        
        $en = $this->fetchTerms('en');
        foreach( array_unique($languages) as $lang )
        {
            $lang = strtolower($lang);
            $data = $lang == 'en'?$en:$this->fetchTerms($lang,$en);
            $strings = "\$GLOBALS['translation']['strings'] = ".var_export($data,true);
            file_put_contents(
                $CONFIG['translation']['data_path'].$lang.'.inc.php', 
                "<?\n$info;\n$strings;\n"
            );
            $this->addContent("<div>Created translation file for $lang</div>");
        }
    }
    
    /**
     * @attribute[RequestParam('term','string')]
     * @attribute[RequestParam('text','string','')]
     */
    function CreateString($term,$text)
    {
        $data = array(array('term'=>$term));
        $data = json_encode($data);
        $this->request(array('action'=>'add_terms','data'=>$data));
        
        if( $text )
        {
            $data = array(array(
                'term' => array('term'=>$term),
                'definition' => array('forms'=>array($text),'fuzzy'=>0)
            ));
            $data = json_encode($data);
            $this->request(array('action'=>'update_language','language'=>'en','data'=>$data));
        }
        
        return parent::DeleteString($term);
    }
}