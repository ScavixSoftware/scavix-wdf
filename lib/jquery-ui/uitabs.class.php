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
	protected $_options = array();

	private $_sortable = false;

    function __initialize($id,$options=array(),$width=false)
	{
		parent::__initialize();

		if( !is_array($options) )
			$options = array($options);

		if( $width )
			$this->set('width',$width."px");

		$this->_options = $options;
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
		$this->_options['selected'] = $tabid;
	}

	function SetTabsSortable($sortable=true)
	{
		$this->_sortable = $sortable;
	}

	private function PrepareOptions()
	{
		$options = "";
		if( isset($this->_options['selected']) && is_int($this->_options['selected']) )
			$options .= "selected: ".$this->_options['selected'].",";
		else if(isset($this->_options['selected']) && array_key_exists($this->_options['selected'], $this->_tabIds) )
		{
			$array = array_flip(array_keys($this->_tabIds));
			$options .= "selected: ".$array[$this->_options['selected']].",";
		}		
		if( isset($this->_options['ajaxOptions']) )
			$options .= "ajaxOptions: {".$this->_options['ajaxOptions']."},";
		if( isset($this->_options['cache']) )
			$options .= "cache: '".$this->_options['cache']."',";
		if( isset($this->_options['collapsible']) )
			$options .= "collapsible: '".$this->_options['collapsible']."',";
		if( isset($this->_options['cookie']) )
			$options .= "cookie: ".$this->_options['cookie'].",";
		if( isset($this->_options['deselectable']) )
			$options .= "deselectable: '".$this->_options['deselectable']."',";
		if( isset($this->_options['disabled']) )
			$options .= "disabled: ".$this->_options['disabled'].",";
		if( isset($this->_options['event']) )
			$options .= "event: '".$this->_options['event']."',";
		if( isset($this->_options['fx']) )
			$options .= "fx: ".$this->_options['fx'].",";
		if( isset($this->_options['idPrefix']) )
			$options .= "idPrefix: '".$this->_options['idPrefix']."',";
		if( isset($this->_options['panelTemplate']) )
			$options .= "panelTemplate: '".$this->_options['panelTemplate']."',";
		if( isset($this->_options['select']) )
		    $options .= "select: ".$this->_options['select'].',';
		if( isset($this->_options['spinner']) )
			$options .= "spinner: '".$this->_options['spinner']."',";
		if( isset($this->_options['tabTemplate']) )
			$options .= "tabTemplate: '".$this->_options['tabTemplate']."',";

		return rtrim($options,',');
	}
}
