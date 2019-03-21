<?php
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
namespace ScavixWDF\Translation;

use stdClass;
use ScavixWDF\Base\AjaxAction;
use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Base\Control;
use ScavixWDF\Controls\Anchor;
use ScavixWDF\Controls\Form\Button;
use ScavixWDF\Controls\Form\CheckBox;
use ScavixWDF\Controls\Form\Form;
use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Controls\Form\TextArea;
use ScavixWDF\Controls\Form\TextInput;
use ScavixWDF\Controls\Table\Table;
use ScavixWDF\JQueryUI\Dialog\uiDialog;
use ScavixWDF\Localization\Localization;
use ScavixWDF\WdfException;

/**
 * <SysAdmin> handler for translations.
 * 
 * @attribute[NoMinify]
 */
class TranslationAdmin extends TranslationAdminBase
{
	var $ds;
	
	function __initialize($title = "", $body_class = false)
    {
        parent::__initialize($title, $body_class);
        if( isset($GLOBALS['CONFIG']['translation']['sync']['scavix_datasource']) && $GLOBALS['CONFIG']['translation']['sync']['scavix_datasource'] )
			$this->ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['scavix_datasource']);
		elseif( isset($GLOBALS['CONFIG']['translation']['sync']['datasource']) && $GLOBALS['CONFIG']['translation']['sync']['datasource'] )
			$this->ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
		else
			WdfException::Raise("ScavixTranslations datasource missing!");
		
		$this->ds->ExecuteSql("CREATE TABLE IF NOT EXISTS `wdf_translations` (
				`lang` VARCHAR(10) NOT NULL,
				`id` VARCHAR(100) NOT NULL,
				`content` TEXT NULL,
				PRIMARY KEY (`lang`, `id`) );");
		
		$this->subnav('Translate', 'TranslationAdmin', 'translate');
		$this->subnav('Import', 'TranslationAdmin', 'import');
    }
	
	private function fetchTerms($lang_code,$defaults = array(),&$unkown=null)
    {
        $rs = $this->ds->ExecuteSql("SELECT id,content FROM wdf_translations WHERE lang=?",$lang_code);
        $res = $defaults;
        $known = [];
        foreach( $rs as $lang )
        {
            $res[$lang['id']] = isset($lang['content'])?"{$lang['content']}":'';
            $known[] = $lang['id'];
        }
        $unkown = array_diff(array_keys($res),$known);
		return $res;
    }
	
	private function _languageSelect($lang)
	{
		global $CONFIG;
		$sel = new Select();
		$sel->SetCurrentValue($lang);
		$known = $sel->CreateGroup('Languages with translations');
		$avail = $sel->CreateGroup('Available languages');
		
		$counts = array();
		foreach( $this->ds->ExecuteSql("SELECT lang,count(*) as cnt FROM wdf_translations GROUP BY lang") as $row )
			$counts[$row['lang']] = intval($row['cnt']);
		$total = max($counts);
        $def = $GLOBALS['CONFIG']['localization']['default_language'];
        $allowed = $this->user->getProperty('languages','all');
		foreach( Localization::get_language_names() as $code=>$name )
		{
            if( $allowed!=='all' && $code != $def && !in_array($code,$allowed) )
                continue;
			if( isset($counts[$code]) )
			{
				$name = "$name ({$counts[$code]})";
				if( $code == $CONFIG['localization']['default_language'] )
					$name .= " [default]";
				else
					$name .= " [". floor($counts[$code]/$total*100) ."%]";
			}
			$sel->AddOption($code,$name,false,(isset($counts[$code])&&$counts[$code]>0)?$known:$avail);
		}
		return $sel;
	}
	
    /**
	 * @internal Fetch action handler
     * @attribute[RequestParam('languages','array',false)]
     */
    function Fetch($languages = false)
    {
        global $CONFIG;
        
        $this->_contentdiv->content("<h1>Fetch strings</h1>");
        
        if( !isDev() )
        {
            $this->_contentdiv->content("<h2>This can only be used in DEV system</h2>");
            $this->_contentdiv->content("<br/><br/>");
            if( $this->user->hasAccess('translationadmin','download') )
                $this->_contentdiv->content(Button::Make('Download translation files as ZIP',"location.href='". buildQuery('translationadmin','download')."/Translations.zip'"));
            return;
        }
        
        $db_languages = $this->ds->ExecuteSql("SELECT DISTINCT lang FROM wdf_translations ORDER BY lang")->Enumerate('lang',false);
		$max = $this->ds->ExecuteScalar("SELECT MAX(cnt) FROM (SELECT count(*) as cnt FROM wdf_translations GROUP BY lang) AS x");
		foreach( $db_languages as $i=>$lang )
		{
			$count = $this->ds->ExecuteScalar("SELECT count(*) FROM wdf_translations WHERE lang=?",$lang);
			
			$db_languages[$i] = new stdClass();
			$db_languages[$i]->name = Localization::getCultureInfo($lang)->EnglishName;
			$db_languages[$i]->code = $lang;
			$db_languages[$i]->percentage = round($count / $max * 100,0);
		}
        
        $div = $this->_contentdiv->content(new Form());
        foreach( $db_languages as $lang )
        {
            $cb = $div->content( CheckBox::Make('languages[]')->setChecked(true) );
            $cb->value = $lang->code;
            $div->content($cb->CreateLabel($lang->name." ({$lang->code}, {$lang->percentage}% complete)"));
            $div->content("<br/>");
        }
        $div->content('<br/>');
        $div->AddSubmit("Create translation files");
        
        if( !$languages )
            return; 
        
        $head = array();
        foreach( $db_languages as $lang )
            $head[$lang->code] = array('percentage_complete'=>$lang->percentage/100, 'percentage_empty'=>(1-$lang->percentage/100), 'syntax_error_qty'=>0);
//        $info = "\$GLOBALS['translation']['properties'] = ".var_export($head,true);
        
        $defaults = $this->fetchTerms($CONFIG['localization']['default_language']);
        
        foreach( array_unique($languages) as $lang )
        {
            $unkown = [];
            $lang = strtolower($lang);
            $data = $lang == $CONFIG['localization']['default_language']?$defaults:$this->fetchTerms($lang,$defaults,$unkown);
            $head[$lang]['unknown'] = $unkown;
        }
        $info = "\$GLOBALS['translation']['properties'] = ".var_export($head,true);
        
        $this->_contentdiv->content('<br/><br/>');
        
        $written_languages = [];
        foreach( array_unique($languages) as $lang )
        {
            $unkown = [];
            $lang = strtolower($lang);
            $data = $lang == $CONFIG['localization']['default_language']?$defaults:$this->fetchTerms($lang,$defaults,$unkown);
            $strings = "\$GLOBALS['translation']['strings'] = ".var_export($data,true);
            $filename = $CONFIG['translation']['data_path'].$lang.'.inc.php';
            $filecontent = "<?php\n$info;\n$strings;\n";
            
            $ret = true;
            set_error_handler(
                function ($severity, $message, $file, $line) use (&$ret) {
                    $this->_contentdiv->content(\ScavixWDF\JQueryUI\uiMessage::Error($message));
                    $this->_contentdiv->content("<br/>");
                    $ret = false;
                }
            );
            file_put_contents(
                $filename, 
                $filecontent
            );
            restore_error_handler();

            if($ret !== false)
            {
                if(file_get_contents($filename) !== $filecontent)
                    $this->_contentdiv->content(\ScavixWDF\JQueryUI\uiMessage::Hint('Content of written file differs ('.$filename.')'));
                else
                {
                    // success
                    $this->_contentdiv->content("<div style='color: green'>Created translation file for <b>$lang</b> &#10003;</div>");
                    $written_languages[] = $lang;
                }
                $this->_contentdiv->content("<br/>");
            }
        }
		
        if(count($written_languages) > 0)
        {
            $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
            $ds->ExecuteSql("DELETE FROM wdf_unknown_strings");
            $ds->ExecuteSql("DELETE FROM wdf_unknown_strings_data WHERE term NOT IN(SELECT id FROM wdf_translations)");
            $this->_contentdiv->content("<div>Cleared the unknown strings tables &#10003;</div>");
		
            foreach( cache_list_keys() as $key )
            {
                if( starts_with($key, 'lang_') )
                    cache_del($key);
            }
            $this->_contentdiv->content("<div>Cleared the string cache &#10003;</div>");

            $this->_contentdiv->content("<br/><br/>");
            $this->_contentdiv->content(Button::Make('Download as ZIP',"location.href='". buildQuery('translationadmin','download')."/Translations.zip'"));
        
            foreach( $written_languages as $lang )
            {
                $lang = strtolower($lang);
                $fn = $CONFIG['translation']['data_path'].$lang.'.inc.php';
                $strings = file_get_contents($fn);

                $fn = "$fn <a style='font-weight:normal' href='javascript:void(0)' onclick='document.getElementById(\"strings_{$lang}\").select(); document.execCommand(\"Copy\");'>copy</a>";

                $this->_contentdiv->content("<br/><br/>");
                $this->_contentdiv->content("<b>$fn</b>");
                $this->_contentdiv->content("<textarea id='strings_{$lang}' style='width: 90%; min-height: 30px'>$strings</textarea>");
            }
        }
    }
	
    /**
	 * @internal Fetch action handler
     * @attribute[RequestParam('languages','array',false)]
     * @attribute[RequestParam('clearbeforeimport','bool',false)]
     */
    function Import($languages = false, $clearbeforeimport = false)
    {
        global $CONFIG;
        
        $this->_contentdiv->content("<h1>Import strings</h1>");
        
        if( !$languages )
        {
            $this->_contentdiv->content("<p>Imports strings from XX.inc.php (in ".$CONFIG['translation']['data_path'].") into the database</p>");
            $div = $this->_contentdiv->content(new Form());
            foreach( glob($CONFIG['translation']['data_path'].'*.inc.php') as $filename )
            {
                $lang = str_replace('.inc.php', '', basename($filename));
                $cb = $div->content( new CheckBox('languages[]') );
                $cb->value = $lang;
                $div->content($cb->CreateLabel(basename($filename)));
                $div->content("<br/>");
            }
            $a = $div->content(new Anchor('#','Select all'));
            $a->script("$('#{$a->id}').click(function(){ $('input[name=\"languages[]\"]','#{$div->id}').attr('checked',true); });");
            $div->content("<br/>");
            $div->content("<br/>");
            $cb = $div->content( new CheckBox('clearbeforeimport') );
            $cb->value = 1;
            $div->content($cb->CreateLabel('Empty language before import'));
            $div->content("<br/>");
            $div->content("<br/>");
            $div->AddSubmit("Import");
            return;
        }
        
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        
        foreach($languages as $lang)
        {
            unset($GLOBALS['translation']['properties']);
            unset($GLOBALS['translation']['strings']);
            $filename = $CONFIG['translation']['data_path'].$lang.'.inc.php';
            include($filename);
            $cnt = count($GLOBALS['translation']['strings']);
            if($cnt > 0)
            {
                $cnt = 0;
                if($clearbeforeimport)
                    $ds->ExecuteSql("DELETE FROM wdf_translations WHERE lang=?", [$lang]);
                foreach($GLOBALS['translation']['strings'] as $k => $v)
                {
                    if( in_array($k,$GLOBALS['translation']['properties'][$lang]['unknown']))
                        continue;
                    $ds->ExecuteSql("REPLACE INTO wdf_translations SET lang=?, id=?, content=?", [$lang, $k, $v]);
                    $cnt++;
                }
                $this->_contentdiv->content("<p>$cnt strings imported for ".$lang."</p>");
            }
            else
                $this->_contentdiv->content("<p>No strings found in ".$filename."</p>");

        }
		
		$ds->ExecuteSql("DELETE FROM wdf_unknown_strings");
		$this->_contentdiv->content("<div>Cleared the unknown strings table</div>");
		
		$this->_contentdiv->content("<div>Done!</div>");
    }
    
    /**
	 * @internal Create new string handler
     * @attribute[RequestParam('term','string')]
     * @attribute[RequestParam('text','string','')]
     */
    function CreateString($term,$text)
    {
        global $CONFIG;
		$text = urldecode($text);
		$this->ds->ExecuteSql("REPLACE INTO wdf_translations(lang,id,content)VALUES(?,?,?)",array($CONFIG['localization']['default_language'],$term,$text));
        cache_del('lang_'.$term);
        return $this->DeleteString($term);
    }
	
	private function _searchQuery($offset,$lang,$search=false,$untranslated=false)
	{
		$def = $GLOBALS['CONFIG']['localization']['default_language'];
		$sql = "select 
			o.id as 'id', o.content as 'def', 
			(select i.content from wdf_translations i where i.id=o.id and i.lang=?) as 'trans' 
		from wdf_translations o where o.lang='$def' {having} order by id asc limit $offset,50";
		if( $untranslated )
			return $this->ds->ExecuteSql(str_replace("{having}","having isnull(trans) or trans=''",$sql),$lang);
		if( !$search )
			return $this->ds->ExecuteSql(str_replace("{having}","",$sql),$lang);
		$s = str_replace(array('_','%'), array('\_','\%'), $this->ds->EscapeArgument($search));
		$s = str_replace(array('?','*'),array('_','%'),$s);
		$s = "%$s%";
		return $this->ds->ExecuteSql(str_replace("{having}","having id like ? or def like ? or trans like ?",$sql),array($lang,$s,$s,$s));
	}
	
	/**
	 * @internal Entry point for translation admin.
	 * @attribute[RequestParam('lang','string',false)]
	 * @attribute[RequestParam('offset','int',0)]
	 * @attribute[RequestParam('search','text','')]
	 * @attribute[RequestParam('untranslated','bool',false)]
	 */
	function Translate($lang,$offset,$search,$untranslated)
	{
		global $CONFIG;
		if( $untranslated ) $search = '';
		$lang = $lang?$lang:$CONFIG['localization']['default_language'];
		$_SESSION['trans_admin_lang'] = $lang;
		$_SESSION['trans_admin_offset'] = $offset;
		$_SESSION['trans_admin_search'] = $search;
		
		$form = $this->content( new Form() )->attr('method','get');
		$form->css('margin-bottom','20px')->action = buildQuery('TranslationAdmin','Translate',['test'=>'bla']);				
		$form->content("Select language: ");
		$form->content( $this->_languageSelect($lang) )
			->script("$('#{self}').change(function(){ $('#{$form->id}').submit(); });")
			->name = 'lang';
		$form->content("&nbsp;&nbsp;&nbsp;And/Or search: ");
		$form->AddText('search',$search);
		$form->AddHidden('offset',0);
		$form->AddSubmit('Search');
		$form->content("<span style='color:gray'>(?:=single char, *:=any/no char)</span>");

		if( $lang != $CONFIG['localization']['default_language'] )
		{
			$form->content("&nbsp;&nbsp;&nbsp;Or ");
		    $form->AddHidden('untranslated',0)->id = 'untranslated';
			Button::Make("Show untranslated","$('#untranslated').val('1').closest('form').submit();")->appendTo($form);
		}
		
		$tab = Table::Make()->addClass('translations all')
			->SetHeader('Term','Default','','Content','')
			->setData('lang',$lang)
			->appendTo($this);

		$rs = $this->_searchQuery($offset,$lang,$search,$untranslated);
		foreach( $rs as $term )
		{
			$def = nl2br(htmlspecialchars($term['def']));
			$ta = TextArea::Make($untranslated?'':$term['trans'])
                ->addClass($term['id'])
                ->attr('rows',count(explode('<br />', $def)) + 1);
			$btn = Button::Make('Save')->addClass('save')->setData('term',$term['id']);
			
            $wrap = Control::Make()
                ->append($ta)
                ->append('<br/>');
            foreach( $this->ds->Query('wdf_unknown_strings_data')->eq('term',$term['id'])->enumerate('value',false,'name') as $k=>$v )
                $wrap->append( "<span class='termdata' title='Sample: {$v}' onclick=\"$(this).closest('.td').find('textarea').insertAtCaret($(this).text());\">{$k}</span>" );
            
            $cpy = Button::Make('&gt;&gt;')->addClass('copy')
                ->setData('term',$term['id'])
                ->setData('def', json_encode($term['def'],JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
                
            $one = Control::Make()
                ->append(Anchor::Make(buildQuery('translationadmin','translateone',['term'=>$term['id']]),$term['id']))
                ->append(Control::Make("span")->addClass('term_action rename')->setData('term', $term['id'])->append('rename'))
                ->append(Control::Make("span")->addClass('term_action remove')->setData('term', $term['id'])->append('remove'))
                ;
            
			$tab->AddNewRow($one,$def,$cpy,$wrap,$btn);
		}

		if($tab->GetCurrentRow())
			$tab->GetCurrentRow()->GetCell(1)->style = 'width: 40%';

		$pi = $rs->GetPagingInfo();
		for($page=1;$page<=$pi['total_pages'];$page++)
		{
			$offset = ($page-1) * $pi['rows_per_page'];
			$label = ($offset+1)."-".($page*$pi['rows_per_page']);
			$label = "$page";
			if( $page == $pi['current_page'] )
				$this->content("<b>$label</b>");
			else
				$this->content(new Anchor(buildQuery('TranslationAdmin','Translate',"lang=$lang&offset=$offset&search=$search&untranslated=".($untranslated?1:0)),"$label"));
			$this->content("&nbsp;");
		}
	}
    
    /**
     * @internal Allow to translate a term in all languages
     * @attribute[RequestParam('term','string','')]
     */
	function TranslateOne($term)
	{
        global $CONFIG;
        $this->content("<h1>Translations for $term</h1>");
        
		$tab = Table::Make()->addClass('translations one')
			->SetHeader('Language','Content','')
			->appendTo($this);
        
        $variables = $this->ds->Query('wdf_unknown_strings_data')->eq('term',$term)->enumerate('value',false,'name');
        
        $started = $this->ds->Query('wdf_translations')->all()->enumerate('lang');
        $languages = array_keys(Localization::get_language_names());
        sort($languages);
        $languages = array_merge($started,$languages);
        array_unshift($languages,$CONFIG['localization']['default_language']); 
        $languages = array_unique($languages);
        
        $def = $GLOBALS['CONFIG']['localization']['default_language'];
        $allowed = $this->user->getProperty('languages','all');
        foreach( Localization::get_language_names() as $lang=>$name )
        {
            if( $allowed!=='all' && $lang != $def && !in_array($lang,$allowed) )
                continue;
            
            $dbrow = $this->ds->Query("wdf_translations")->eq('lang',$lang)->eq('id',$term)->current();
            $row = $dbrow?:['lang'=>$lang,'id'=>$term,'content'=>''];
            
            $ta = new TextArea($row['content']);
			$ta->class = $row['id'];
			$ta->rows = count(explode('<br />', nl2br($row['content']))) + 1;
            
            $btn = new Button('Save');
			$btn->addClass('save')->setData('term',$row['id'])->setData('lang',$lang);
            
            $wrap = Control::Make()
                ->append($ta)
                ->append('<br/>');
            foreach( $variables as $k=>$v )
                $wrap->append( "<span class='termdata' title='Sample: {$v}' onclick=\"$(this).closest('.td').find('textarea').insertAtCaret($(this).text());\">{$k}</span>" );
            
            if( in_array($lang,$started) )
                $tab->AddNewRow("$name ({$lang})",$wrap,$btn);
            else
                $others[] = ["$name ({$lang})",$wrap,$btn];
        }
        
        $this->content("<h1>Other languages</h1>");
        $tab = Table::Make()->addClass('translations one')
			->SetHeader('Language','Content','')
			->appendTo($this);
        foreach( $others as $row )
            $tab->NewRow($row);
	}
	
	/**
	 * @internal Save string handler
	 * @attribute[RequestParam('lang','string')]
     * @attribute[RequestParam('term','string')]
     * @attribute[RequestParam('text','string','')]
     */
	function SaveString($lang,$term,$text)
	{
		$text = urldecode($text);
		if( $text )
			$this->ds->ExecuteSql("REPLACE INTO wdf_translations(lang,id,content)VALUES(?,?,?)",array($lang,$term,$text));
		else
			$this->ds->ExecuteSql("DELETE FROM wdf_translations WHERE lang=? AND id=?",array($lang,$term));
        cache_del('lang_'.$term);
		return AjaxResponse::None();
	}
	
	/**
	 * Imports a file with translations into the translation system.
	 * 
	 * File must contain vaid JSON data with key-value pairs of strings in an array.
	 * Sample:
	 * <code json>
	 * [
	 *   {"term":"TXT_TERM1","content":"My contents of terms 1"},
	 *   {"or":"TXT_TERM2","can_be":"My contents of terms 2"},
	 *   {"whatever":"TXT_TERM3","wtf_too":"My contents of terms 3"}
	 * ]
	 * </code>
	 * Notes:
	 * - The key names will be ignored, only the order counts
	 * - The contents must be in the language defined by the `$lang` parameter
	 * - The uploaded file must be given as 'json_file' (`$_FILES['json_file']`)
	 * @param string $lang The language the contents are given in
	 * @return void
	 * @internal
	 * @attribute[RequestParam('lang','string',false)]
	 */
	function ImportJSON($lang)
	{
		global $CONFIG;
		$lang = $lang?$lang:$CONFIG['localization']['default_language'];
		$is_default = $lang == $CONFIG['localization']['default_language'];
		
		if( isset($_FILES['json_file']) )
		{
			$json_string = file_get_contents($_FILES['json_file']['tmp_name']);
			unlink($_FILES['json_file']['tmp_name']);
			$count = 0; $unknowns = "";
			
			if( !$is_default ) 
				$knowns = $this->ds->ExecuteSql("SELECT DISTINCT id FROM wdf_translations WHERE lang=?",$CONFIG['localization']['default_language'])
					->Enumerate('id',false);
			
			foreach( json_decode($json_string,true) as $entry )
			{
				$entry = array_values($entry);
				if( count($entry) < 2 || !$entry[1] )
					continue;
				$this->ds->ExecuteSql("REPLACE INTO wdf_translations(lang,id,content)VALUES(?,?,?)",array($lang,$entry[0],$entry[1]));
				$count++;
				
				if( !$is_default && !in_array($entry[0],$knowns) )
				{
					$unknowns[] = $entry[0];
					default_string($entry[0],"Imported from $lang: ".$entry[1]);
				}
			}
			if( $is_default )
				$this->ds->ExecuteSql("DELETE FROM wdf_unknown_strings WHERE term IN(SELECT id FROM wdf_translations WHERE lang=?)",$lang);
			else
			{
				log_debug($unknowns);
				translation_add_unknown_strings($unknowns);
			}
			
			$this->content("<h2>$count terms imported</h2>");
		}
		
		$form = $this->content( new Form() );
		$form->content($this->_languageSelect($lang))->name = 'lang';
		$form->AddFile('json_file');
		$form->AddSubmit('Import');		
	}
	
	/**
	 * @internal Renames a term.
	 * 
	 * Sometimes you may want to correct a terms name, so use this one.
	 * @param string $term The original term
	 * @param string $new_term The new term name
	 * @return void
	 * @attribute[RequestParam('term','string')]
	 * @attribute[RequestParam('new_term','string',false)]
	 */
	function Rename($term,$new_term)
	{
		if( !$new_term )
		{
			$dlg = new uiDialog('Rename term');
			$dlg->content("Enter new term: ");
			$ti = $dlg->content(new TextInput("{$term}[NT]"));
			$dlg->AddButton('Rename', "function(){ wdf.controller.post('Rename',{term:'{$term}[NT]',new_term:$('#{$ti->id}').val()}); }");
			$dlg->AddCloseButton('Cancel');
			return $dlg;
		}
		$this->ds->ExecuteSql("UPDATE wdf_translations SET id=? WHERE id=?",array($new_term,$term));
        $this->ds->LogLastStatement();
		return AjaxResponse::Redirect('TranslationAdmin','Translate', array(
			'lang' => $_SESSION['trans_admin_lang'],
			'offset' => $_SESSION['trans_admin_offset'],
			'search' => $_SESSION['trans_admin_search'],
		));
	}
	
	/**
	 * @internal Removes a term.
	 * 
	 * Removes a term from all translations.
	 * @param string $term The term to remove
	 * @return void
	 * @attribute[RequestParam('term','string')]
	 */
	function Remove($term)
	{
		default_string("TITLE_REMOVE_TERM","Remove term");
		default_string("TXT_REMOVE_TERM","Do you really want to remove this term? This cannot be undone!");
		
		if( !AjaxAction::IsConfirmed("REMOVE_TERM") )
            return AjaxAction::Confirm("REMOVE_TERM", 'TranslationAdmin', 'Remove', array('term'=>"{$term}[NT]"));
		
		$this->ds->ExecuteSql("DELETE FROM wdf_translations WHERE id=?",$term);
		return AjaxResponse::Redirect('TranslationAdmin','Translate', array(
			'lang' => $_SESSION['trans_admin_lang'],
			'offset' => $_SESSION['trans_admin_offset'],
			'search' => $_SESSION['trans_admin_search'],
		));
	}
}