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

class uiTabs extends uiTemplate
{
	protected $_tabContent = array();
	protected $_tabIds = array();

	private $_sortable = false;

    function __initialize($id,$options=array(),$width=false)
	{
		parent::__initialize();

		if( !is_array($options) )
			$options = array($options);

		if( $width )
			$this->set('width',$width."px");

		$this->Options = $options;
		$this->set('id',$id);
	}

	public function WdfRender()
	{
		$this->set('tab_content',$this->_tabContent);
		$this->set('tab_ids',$this->_tabIds);
		$this->set('options',$this->PrepareOptions());
		$this->set('sortable',$this->_sortable);

		return parent::WdfRender();
	}

	function AddTab($tabid,$content="",$label=false)
	{
		if( !$label )
			$label = $tabid;

		$this->_tabIds[$tabid] = $label;
		$this->_tabContent[$tabid] = array($content);
	}

	function AddToTab($tabid,$content)
	{
		$this->_tabContent[$tabid][] = $content;
	}

	function SelectTab($tabid)
	{
		$this->Options['selected'] = $tabid;
	}

	function SetTabsSortable($sortable=true)
	{
		$this->_sortable = $sortable;
	}

	private function PrepareOptions()
	{
		$options = "";
		if( isset($this->Options['selected']) && is_int($this->Options['selected']) )
			$options .= "selected: ".$this->Options['selected'].",";
		else if(isset($this->Options['selected']) && array_key_exists($this->Options['selected'], $this->_tabIds) )
		{
			$array = array_flip(array_keys($this->_tabIds));
			$options .= "selected: ".$array[$this->Options['selected']].",";
		}		
		if( isset($this->Options['ajaxOptions']) )
			$options .= "ajaxOptions: {".$this->Options['ajaxOptions']."},";
		if( isset($this->Options['cache']) )
			$options .= "cache: '".$this->Options['cache']."',";
		if( isset($this->Options['collapsible']) )
			$options .= "collapsible: '".$this->Options['collapsible']."',";
		if( isset($this->Options['cookie']) )
			$options .= "cookie: ".$this->Options['cookie'].",";
		if( isset($this->Options['deselectable']) )
			$options .= "deselectable: '".$this->Options['deselectable']."',";
		if( isset($this->Options['disabled']) )
			$options .= "disabled: ".$this->Options['disabled'].",";
		if( isset($this->Options['event']) )
			$options .= "event: '".$this->Options['event']."',";
		if( isset($this->Options['fx']) )
			$options .= "fx: ".$this->Options['fx'].",";
		if( isset($this->Options['idPrefix']) )
			$options .= "idPrefix: '".$this->Options['idPrefix']."',";
		if( isset($this->Options['panelTemplate']) )
			$options .= "panelTemplate: '".$this->Options['panelTemplate']."',";
		if( isset($this->Options['select']) )
		    $options .= "select: ".$this->Options['select'].',';
		if( isset($this->Options['spinner']) )
			$options .= "spinner: '".$this->Options['spinner']."',";
		if( isset($this->Options['tabTemplate']) )
			$options .= "tabTemplate: '".$this->Options['tabTemplate']."',";

		return rtrim($options,',');
	}
}
