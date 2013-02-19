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
default_string('TITLE_DIALOG', 'Dialog');

class uiDialog extends uiControl
{
	protected $Options = array();
	protected $Buttons = array();
	protected $CloseButton = null;
	var $CloseButtonAction = null;

	function __initialize($title="TITLE_DIALOG", $options=array())
	{
		parent::__initialize("div");
		$this->title = $title;

		$this->Options = array_merge(array(
				'autoOpen'=>true,
				'modal'=>true,
				'resizable'=>false,
				'draggable'=>false,
				'width'=>350,
				'height'=>150,
				'open'=>"function(){ $(this).parent().find('.ui-button').button('enable'); }",
			),$options);
		
		$rem = system_is_ajax_call()?".remove()":'';
		$this->CloseButtonAction = "function(){ $('#{$this->id}').dialog('close')$rem; }";
	}

	function SetOption($name,$value)
	{
		$this->Options[$name] = $value;
		return $this;
	}

	function PreRender($args=array())
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
			$this->Options['buttons'] = $this->Buttons;
			$tmp = $this->_script;
			$this->_script = array();
			$this->script("try{ $('#{$this->id}').dialog(".system_to_json($this->Options)."); }catch(ex){ wdf.debug(ex); }");
			$this->script("$('#{$this->id}').parent().find('.ui-dialog-buttonpane .ui-button').click(function(){ $(this).parent().find('.ui-button').button('disable'); });");
			$this->_script = array_merge($this->_script,$tmp);

			foreach( $this->_script as $s )
			{
				$controller->addDocReady($s);
			}
		}
		return parent::PreRender($args);
	}
	/**
	 * Look at system.php system_to_json($value) documentation
	 */
	function AddButton($label,$action)
	{
		if( !starts_with($action, '[jscode]') && !starts_with($action, 'function') )
			$action = "function(){ $action }";
		$this->Buttons[$label] = $action;
		return $this;
	}
	function SetButton($label,$action)
	{
		return $this->AddButton($label, $action);
	}

	function AddCloseButton($label, $action = false)
	{
		$this->CloseButton = $label;
		if($action !== false)
			$this->CloseButtonAction = $action;
		return $this;
	}
}
