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
namespace ScavixWDF\JQueryUI\Dialog;

use ScavixWDF\JQueryUI\uiControl;

default_string('TITLE_DIALOG', 'Dialog');

/**
 * Wraps a jQueryUI Dialog
 * 
 * See http://jqueryui.com/dialog/
 */
class uiDialog extends uiControl
{
	protected $Buttons = [];
	protected $CloseButton = null;
	public $CloseButtonAction = null;
    
    public static $DESTROY_ON_CLOSE = false;        // destroy the dialog when it's closed. To keep backward-comaptibility, this new beahviour can only be enabled manually

	/**
	 * @param string $title The dialogs title
	 * @param array $options See http://api.jqueryui.com/dialog/
	 */
	function __construct($title="TITLE_DIALOG", $options=[])
	{
		parent::__construct("div");
		$this->title = $title;
		$tit_script = $this->title?"":"$(this).parent().find('.ui-dialog-titlebar').hide();";

		$this->Options = array_merge(array(
				'autoOpen'=>true,
				'modal'=>true,
				'resizable'=>false,
				'draggable'=>false,
				'width'=>350,
				'height'=>150,
				'open'=>"function(){ $(this).parent().find('.ui-button').button('enable');$tit_script }",
			),$options);
		
        if(uiDialog::$DESTROY_ON_CLOSE)
        {
            // remove the dialog on close
            $this->Options = array_merge(array(
				'close'=>"function(){ try{ $(this).dialog('destroy'); }catch(noop){} $(this).remove(); }",
			),$this->Options );
    		$this->CloseButtonAction = "function(){ $('#{$this->id}').removeDialog(); }";
        }
        else
        {
            // old behaviour
            $rem = system_is_ajax_call()
                ?"$('#{$this->id}').removeDialog();"
                :"try{ $('#{$this->id}').dialog('close'); }catch(noop){}";
            $this->CloseButtonAction = "function(){ $rem }";
        }
		
		$this->InitFunctionName = false;
	}

	/**
	 * @override
	 */
	function PreRender($args=[])
	{
		if( count($args) > 0 )
		{
			$controller = &$args[0];
			// just to render close button with the right id
			if( !is_null($this->CloseButton) )
			{
				$temp = array( $this->CloseButton => $this->CloseButtonAction );
				$this->Buttons = array_merge($this->Buttons, $temp);
			}
			
            $close_action = system_is_ajax_call()
                ?"$('#{$this->id}').removeDialog();"
                :"try{ $('#{$this->id}').dialog('close'); }catch(noop){}";
			
			foreach( $this->Buttons as $label=>$action )
			{
				if( !starts_with($action, '[jscode]') && !starts_with($action, 'function') )
					$action = "function(){ $action }";
				$this->Buttons[$label] = str_replace("{close_action}", $close_action, $action);
			}

			$this->Options['buttons'] = $this->Buttons;
			$tmp = $this->_script;
			$this->_script = [];
			$this->script("try{ $('#{$this->id}').dialog(".system_to_json($this->Options)."); }catch(ex){ wdf.debug(ex); }");
			$this->script("$('#{$this->id}').parent().find('.ui-dialog-buttonpane .ui-button').click(function(){ $(this).parent().find('.ui-button').button().prop('disabled', true).addClass( 'ui-state-disabled' ); });");
			$this->_script = array_merge($this->_script,$tmp);

            if(!system_is_ajax_call())
            {
                foreach( $this->_script as $s )
                {
                    $controller->addDocReady($s);
                }
            }
		}
		return parent::PreRender($args);
	}
	
	/**
	 * Adds a button to the dialog.
	 * 
	 * @param string $label Button text
	 * @param string $action JS code for button click event
	 * @param string $prepend If true will prepend the button, else append
	 * @return static
	 */
	function AddButton($label,$action,$prepend=false)
	{
		if( !starts_with($action, '[jscode]') && !starts_with($action, 'function') )
			$action = "function(){ $action }";
        
        if( $prepend )
            $this->Buttons = array_merge([$label=>$action],$this->Buttons);
        else
            $this->Buttons[$label] = $action;
		return $this;
	}
	
	/**
	 * @shortcut <uiDialog::AddButton>
	 */
	function SetButton($label,$action)
	{
		return $this->AddButton($label, $action);
	}

	/**
	 * Adds a close button.
	 * 
	 * @param string $label Close button text
	 * @param string $action Action to be performed on click, defaults to the standard close action
	 * @return static
	 */
	function AddCloseButton($label, $action = false)
	{
		$this->CloseButton = $label;
		if($action !== false)
			$this->CloseButtonAction = $action;
		return $this;
	}
}
