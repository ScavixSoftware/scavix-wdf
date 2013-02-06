<?
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

class Table extends uiControl
{
    var $header = false;
    var $footer = false;
	var $colgroup = false;
    var $current_row_group = false;
    var $current_row = false;
    var $current_cell = false;

    var $Caption = false;

	var $RowGroupOptions = array();
	var $RowOptions = array();
	var $ColFormats = array();
	var $Culture = false;
	
	const RENDER_MODE_NORMAL = 0;
	const RENDER_MODE_JQUERYUI = 1;
	var $RenderMode = 0;
	
	function __initialize()
	{
		parent::__initialize("div");
		$this->class = 'table';
		$this->script("$('#{self}').table();");
	}
	
	static function Make(){ return new Table(); }

	function SetColFormat($index,$format,$blank_if_false=false,$conditional_css=array())
	{
		$this->ColFormats[$index] = new CellFormat($format, $blank_if_false, $conditional_css);
		if( array_key_exists('copy',$conditional_css) )
		{
			$this->ColFormats[$index]->conditional_css['copy'] = $this->ColFormats[$this->ColFormats[$index]->conditional_css['copy']];
		}
		return $this;
	}
	
	function GetColFormat($index)
	{
		if( !isset($this->ColFormats[$index]) )
			return new CellFormat('%s');
		return $this->ColFormats[$index];
	}

	function Clear()
	{
		$this->current_row_group = false;
		$this->current_row = false;
		$this->current_cell = false;
		$this->_content = array();
	}

    function &Header()
    {
        if( !$this->header )
            $this->header = new THead();
        return $this->header;
    }

    function &Footer()
    {
        if( !$this->footer )
            $this->footer = new TFoot();
        return $this->footer;
    }

	function &ColGroup()
	{
        if( !$this->colgroup )
            $this->colgroup = new ColGroup();
        return $this->colgroup;
	}

    function &NewRowGroup($options=false)
    {
		if( !$options )
			$options = $this->RowGroupOptions;
        $this->current_row_group = new TBody($options,"tbody",$this);
		$this->current_row_group->RowOptions = $this->RowOptions;

        $this->content($this->current_row_group);
        return $this->current_row_group;
    }

    function &NewRow($data=false,$options=false)
    {
        if( !$this->current_row_group )
            $this->NewRowGroup();

		if( !$options )
			$options = $this->RowOptions;

		$this->current_row =& $this->current_row_group->NewRow($data,$options);

        return $this->current_row;
    }

    function &NewCell($content=false)
    {
        if( !$this->current_cell )
            $this->NewRow();

		$this->current_cell = $this->current_row_group->NewCell($content);
        return $this->current_cell;
    }

	function PreRender($args=array())
	{
		if( isset($this->RowOptions['hoverclass']) && $this->RowOptions['hoverclass'] )
		{
			$over = "function(){ $(this).addClass('{$this->RowOptions['hoverclass']}') }";
			$out  = "function(){ $(this).removeClass('{$this->RowOptions['hoverclass']}') }";
			$rowhover = "$('#{$this->id} tbody tr').hover($over,$out);";
			$this->script($rowhover);
		}
		parent::PreRender($args);
	}
	
	function WdfRender()
    {
        if( $this->footer )
            $this->_content = array_merge(array($this->footer),$this->_content);
        if( $this->header )
            $this->_content = array_merge(array($this->header),$this->_content);

		if( $this->colgroup )
			$this->_content = array_merge(array($this->colgroup),$this->_content);

        if( $this->Caption )
        {
            if( !($this->Caption instanceof Control) )
            {
                $tmp = new Control("div");
                $tmp->content($this->Caption);
				$tmp->class = 'caption';
				$this->Caption = $tmp;
            }
            $this->_content = array_merge(array($this->Caption),$this->_content);
        }

		if( $this->RenderMode == self::RENDER_MODE_JQUERYUI )
		{
			$this->addClass('ui-widget ui-widget-content ui-corner-all');
			if( $this->header ) $this->header->addClass('ui-widget-header');
			if( $this->Caption ) $this->Caption->addClass('ui-widget-header');
			if( $this->footer ) $this->footer->addClass('ui-widget-content');
		}
		
        foreach( $this->_content as &$c )
        {
			if( !is_object($c) || (get_class($c) != "TBody") )
				continue;

            foreach( $c->_content as $r )
			{
				if( !($r instanceof Tr) )
					continue;

				$rcnt = count($r->_content);
				for($i=0; $i<$rcnt; $i++)
				{
					if( $r->_content[$i]->CellFormat )
						$r->_content[$i]->CellFormat->Format($r->_content[$i], $this->Culture);
					elseif( isset($this->ColFormats[$i]) )
						$this->ColFormats[$i]->Format($r->_content[$i], $this->Culture);
				}
			}
        }
		return parent::WdfRender();
    }
	
/* --------------- High level methods returning $this for easy usage --------------------- */
	
	/**
	 * Just sets the caption
	 */
	function SetCaption($cap)
	{
		$this->Caption = $cap;
		return $this;
	}
	
	/**
	 * Takes all arguments given and uses each as row-title
	 */
	function SetHeader()
	{
		$this->Header()->NewRow(func_get_args());
		return $this;
	}
	
	/**
	 * Takes all arguments given and uses each as row-title
	 */
	function SetFooter()
	{
		$this->Footer()->NewRow(func_get_args());
		return $this;
	}
	
	/**
	 * Same as NewRowGroup($options) but returns $this to allow method chaining
	 */
	function AddNewRowGroup($options=false)
	{
		$this->NewRowGroup($options);
		return $this;
	}
	
	/**
	 * Adds a new row, takes all arguments given and uses each as new data-cell
	 */
	function AddNewRow()
	{
		$this->NewRow(func_get_args());
		return $this;
	}
	
	/**
	 * Takes one argument for each (previously set) column.
	 * possible values: l, r, c (or: left, right, center) as strings
	 * sample $tab->SetAlignment('l','l','c','r') when there are 4+ columns
	 * to skip a column just pass an empty string: $tab->SetAlignment('l','','','r')
	 */
	function SetAlignment()
	{
		$cg = $this->ColGroup();
		$head = $this->Header();
		foreach( func_get_args() as $i=>$a )
		{
			switch( strtolower($a) )
			{
				case 'l':
				case 'left':
					$cg->SetCol($i,false,'left');
					$head->GetCell($i)->align = 'left';
					break;
				case 'r':
				case 'right':
					$cg->SetCol($i,false,'right');
					$head->GetCell($i)->align = 'right';
					break;
				case 'c':
				case 'center':
					$cg->SetCol($i,false,'center');
					$head->GetCell($i)->align = 'center';
					break;
			}
		}
		return $this;
	}
	
	/**
	 * Takes one argument for each (previously set) column.
	 * possible values: see CellFormat class
	 * sample $tab->SetFormat('int','f2') when there are 2+ columns
	 * to skip a column just pass an empty string: $tab->SetFormat('int','','','f2')
	 */
	function SetFormat()
	{
		foreach( func_get_args() as $i=>$f )
		{
			if( $f == "" )
				continue;
			$this->SetColFormat($i, $f);
		}
		return $this;
	}
	
	/**
	 * Just sets the culture.
	 */
	function SetCulture($ci)
	{
		$this->Culture = $ci;
		return $this;
	}
	
	/**
	 * Just sets the rendering mode. 
	 * Possible values are Table::RENDER_MODE_NORMAL and Table::RENDER_MODE_JQUERYUI
	 */
	function SetRenderMode($mode)
	{
		$this->RenderMode = $mode;
		return $this;
	}
	
	var $_actions = false;
	var $_rowModels = array();
	var $_actionHandler = array();
	var $_sortHandler = false;
	
	function AddDataToRow($model)
	{
		if( !$this->current_row )
			throw new Exception("No row added");
		$this->current_row->id = $this->current_row->_storage_id;
		$this->_rowModels[$this->current_row->id] = $model;
		return $this;
	}
	
	function GetRowModel($row_id)
	{
		return $this->_rowModels[$row_id];
	}
		
	function AddRowAction($icon,$label,$handler=false,$method=false)
	{
		if( !$this->_actions )
		{
			$this->_actions = $this->content(new Control('div'))->css('display','none')->css('position','absolute')->addClass('ui-table-actions');
			//$this->_actions->addClass('ui-state-default');
		}
		
		$ra = new Control('span');
		$ra->class = "ui-icon ui-icon-$icon";
		$ra->title = $label;
		$ra->id = $ra->_storage_id;
		$ra->setData('action',$icon);
		
		$this->_actions->content( $ra->wrap('div') );
		
		if( $handler && $method )
			$this->_actionHandler[$icon] = array($handler,$method);
		
		store_object($this);
		return $this;
	}
	
	/**
	 * @attribute[RequestParam('action','string')]
	 * @attribute[RequestParam('row','string')]
	 */
	function OnActionClicked($action,$row)
	{
		if( isset($this->_actionHandler[$action]) )
		{
			$model = $this->_rowModels[$row];
			return call_user_func_array($this->_actionHandler[$action],array($this,$action,$model,$row));
		}
		log_warn("No handler defined for $action");
		return AjaxResponse::None();
	}
	
	function Sortable($handler,$method)
	{
		$this->_sortHandler = array($handler,$method);
		$s = "wdf.post('{self}/OnReordered',{rows:$('#{self} .tbody .tr').enumAttr('id')}); $('#{self} .ui-table-actions').removeClass('sorting');";
		$s = "$('#{self} .tbody').sortable({distance:5,update: function(){ $s }, start:function(){ $('#{self} .ui-table-actions').addClass('sorting').hide(); } });";
		$this->script($s);
		store_object($this);
		return $this;
	}
	
	/**
	 * @attribute[RequestParam('rows','array',array())]
	 */
	function OnReordered($rows)
	{
		return call_user_func_array($this->_sortHandler,array($this,$rows));
	}
}
