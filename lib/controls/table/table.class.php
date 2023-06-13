<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls\Table;

use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Base\Control;
use ScavixWDF\Controls\Anchor;
use ScavixWDF\Localization\CultureInfo;
use ScavixWDF\WdfException;

/**
 * An HTML table in DIV notation.
 * 
 */
class Table extends Control
{
	/** @var THead */
    public $header;
	/** @var TFoot */
    public $footer;
	public $colgroup = false;
    public $current_row_group = false;
    public $current_row = false;
    public $current_cell = false;
    public $alignments = false;

    public $Caption = false;

	public $RowGroupOptions = [];
	public $RowOptions = [];
	public $ColFormats = [];
	public $Culture = false;
	
	public $DataCallback = false;
	
	public $ItemsPerPage = false;
	public $CurrentPage = false;
	public $MaxPagesToShow = false;
	public $TotalItems = false;
	public $HidePager = false;
    public $PagerAtTop = false;
    public $ShowTotalText = false;
    
    public $PersistName = false;
    public $force_ajax_dependenciesloading = false;
    
    public $OnPageChanged;
	
	function __construct()
	{
		parent::__construct("div");
		$this->class = 'table';
        if(system_is_ajax_call())
            $this->force_ajax_dependenciesloading = true;
	}
    
    protected final function checkCallIsCorrectlyListingWrapped($hard=false)
    {
        if( !$this->_parent || !($this->par() instanceof \ScavixWDF\Controls\Listing\WdfListing) )
            return;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace);
        $cls = \ScavixWDF\Controls\Listing\WdfListing::class;
        $stack = [];
        foreach( $trace as $entry )
        {
            if( count($stack)>20 || is_in($entry['function'],"system_invoke_request","system_execute") )
                break;
            $stack[] = $entry['file'].':'.$entry['line'];
            if( !isset($entry['class']) )
                continue;
            if( $entry['class'] == $cls || is_subclass_of($entry['class'],$cls) )
                return;
        }
        $method = $trace[0]['class'].$trace[0]['type'].$trace[0]['function'];
        $msg = "Invalid call to '$method' on a listing-owned table. Use Listings wrapper method instead.";
        if( $hard )
            WdfException::Raise($msg);
        log_warn($msg."\n\t".implode("\n\t",$stack));
    }
    
    protected $persistance_storage;
    protected function storage()
    {
        if( !$this->persistance_storage )
            $this->persistance_storage = \ScavixWDF\Wdf::GetBuffer("table_storage")->mapToSession("table_storage");
        return $this->persistance_storage;
    }
    
    protected function getSetting($name,$default=false)
    {
        if( !$this->PersistName )
            return $default;
        return $this->storage()->get("{$this->PersistName}_{$name}", $default);
    }
    
    protected function hasSetting($name)
    {
        if( !$this->PersistName )
            return false;
        return $this->storage()->has("{$this->PersistName}_{$name}");
    }
    
    protected function setSetting($name,$value)
    {
        if( $this->PersistName )
            $this->storage()->set("{$this->PersistName}_{$name}", $value);
    }
    
    protected function delSetting($name)
    {
        if( $this->PersistName )
            $this->storage()->del("{$this->PersistName}_{$name}");
    }
    
    protected function TriggerOnPageChanged()
    {
        if( $this->OnPageChanged )
        {
            $f = $this->OnPageChanged;
            $f("{$this->PersistName}_page",$this->CurrentPage);
        }
    }
    
    function __collectResourcesInternal($template,&$static_stack = [])
	{
        if(system_is_ajax_call() && !$this->force_ajax_dependenciesloading)
            return [];
        return parent::__collectResourcesInternal($template,$static_stack);
    }
	
	/**
	 * Sets the format for a specific column.
	 * 
	 * @param int $index Zero based column index
	 * @param string $format See <CellFormat> for explanation
	 * @param bool $blank_if_false If shall be empty if content is false (that may be 0 or '' too)
	 * @param array $conditional_css See <CellFormat> for explanation
	 * @return static
	 */
	function SetColFormat($index,$format,$blank_if_false=false,$conditional_css=[])
	{
		$this->ColFormats[$index] = new CellFormat($format, $blank_if_false, $conditional_css);
		if( array_key_exists('copy',$conditional_css) )
		{
			$i = intval($this->ColFormats[$index]->conditional_css['copy']);
			$this->ColFormats[$index]->conditional_css['copy'] = $this->ColFormats[$i];
		}
		return $this;
	}
	
	/**
	 * Gets the <CellFormat> for a column.
	 * 
	 * @param int $index Zero based column index
	 * @return CellFormat The <CellFormat> object
	 */
	function GetColFormat($index)
	{
		if( !isset($this->ColFormats[$index]) )
			return new CellFormat('%s');
		return $this->ColFormats[$index];
	}

	/**
	 * Clears the complete table.
	 * 
	 * @return static
	 */
	function Clear()
	{
		$this->current_row_group = false;
		$this->current_row = false;
		$this->current_cell = false;
		if( $this->_actions )
			$this->content($this->_actions,true);
		else
			$this->clearContent();
        return $this;
	}

	/**
	 * Gets the table header.
	 * 
	 * Creates one if needed.
	 * @return THead The tables header
	 */
    function &Header()
    {
        if( !$this->header )
            $this->header = new THead();
        return $this->header;
    }

	/**
	 * Gets the table footer.
	 * 
	 * Creates one if needed.
	 * @param bool $clear If true deleted previously set footer
	 * @return TFoot The tables footer
	 */
    function &Footer($clear=false)
    {
        if( $clear || !$this->footer )
            $this->footer = new TFoot();
        return $this->footer;
    }

	/**
	 * Gets the <ColGroup> definition
	 * 
	 * Creates one if needed.
	 * @return ColGroup The tables <ColGroup> object
	 */
	function &ColGroup()
	{
        if( !$this->colgroup )
            $this->colgroup = new ColGroup();
        return $this->colgroup;
	}

	/**
	 * Creates a new row group and sets it the current
	 * 
	 * Newly created rows will then be added to this group.
	 * @param array $options See <TBody> for options
	 * @return TBody The row group
	 */
    function &NewRowGroup($options=false)
    {
		if( !$options )
			$options = $this->RowGroupOptions;
        $this->current_row_group = new TBody($options,"tbody",$this);
		$this->current_row_group->RowOptions = $this->RowOptions;

        $this->content($this->current_row_group);
        return $this->current_row_group;
    }

	/**
	 * Creates a new row.
	 * 
	 * Will be added to the current row group (which wis created if none yet).
	 * @param array $data Data to be added to the row automatically
	 * @param array $options Rows options, see <TBody::NewRow>
	 * @return Tr The new <Tr> object
	 */
    function &NewRow($data=false,$options=false)
    {
        if( !$this->current_row_group )
            $this->NewRowGroup();

		if( !$options )
			$options = $this->RowOptions;

		$this->current_row =& $this->current_row_group->NewRow($data,$options);

        return $this->current_row;
    }

	/**
	 * Creates a new cell
	 * 
	 * New row will be created if there's not one already.
	 * @param mixed $content Content to be added to the cell automatically
	 * @return Td The new <Td> object
	 */
    function &NewCell($content=false)
    {
        if( !$this->current_cell )
            $this->NewRow();

		$this->current_cell = $this->current_row_group->NewCell($content);
        return $this->current_cell;
    }
	
	/**
	 * Returns the current row, if any.
	 * 
	 * @return Tr The current row object or false
	 */
	function GetCurrentRow()
	{
		if( !$this->current_row_group )
            $this->NewRowGroup();
		return $this->current_row_group->GetCurrentRow();
	}

	/**
	 * @override
	 */
	function PreRender($args=[])
	{
		if ($this->ItemsPerPage && !$this->HidePager)
		{
			$opts = [
				'top_pager' => $this->ItemsPerPage && !$this->HidePager && $this->PagerAtTop,
				'bottom_pager' => $this->ItemsPerPage && !$this->HidePager
			];
			$this->data('options',$opts);
		}
        $this->script("wdf.tables.init('#{self}');");
		if( isset($this->RowOptions['hoverclass']) && $this->RowOptions['hoverclass'] )
		{
			$over = "function(){ $(this).addClass('{$this->RowOptions['hoverclass']}') }";
			$out  = "function(){ $(this).removeClass('{$this->RowOptions['hoverclass']}') }";
			$rowhover = "$('#{$this->id} tbody tr').hover($over,$out);";
			$this->script($rowhover);
		}
		parent::PreRender($args);
	}
	
	protected function _ensureCaptionObject()
	{
		if( $this->Caption && !($this->Caption instanceof Control) )
        {
			$tmp = new Control("div");
			$tmp->content($this->Caption);
			$tmp->class = 'caption';
			$this->Caption = $tmp;
		}
	}
	
	/**
	 * @override 
	 */
	function WdfRender()
    {
		if( $this->DataCallback )
		{
			$this->Clear();
			$args = array($this);
			system_call_user_func_array_byref($this->DataCallback[0], $this->DataCallback[1], $args);
		}
			
        if( $this->footer )
            $this->prepend($this->footer);
        if( $this->header )
            $this->prepend($this->header);

		if( $this->colgroup )
			$this->prepend($this->colgroup);

        if( $this->Caption )
        {
			$this->_ensureCaptionObject();
            $this->prepend($this->Caption);
        }
		
        foreach( $this->_content as &$c )
        {
			if( !is_object($c) )
				continue;
			if( !($c instanceof TBody) )
				continue;
            foreach( $c->_content as $r )
			{
				if( !($r instanceof Tr) )
					continue;
                $r->FormatCells($this);
			}
        }
        
		
//        log_debug(__METHOD__, $this->TotalItems);
//        $this->setData('rowcount', $this->TotalItems);
        if( $this->ItemsPerPage && !$this->HidePager )
		{
			$pager = $this->RenderPager();
			$this->content($pager);
		}
        $res = parent::WdfRender();
        if( $this->DataCallback )
			$this->Clear();

		return $res;
    }
	
/* --------------- High level methods returning $this for easy usage --------------------- */
	
	/**
	 * Just sets the caption.
	 * 
	 * @param string $cap Caption text
	 * @return static
	 */
	function SetCaption($cap)
	{
		$this->Caption = $cap;
		return $this;
	}
	
	/**
	 * Takes all arguments given and uses each as row-title.
	 * 
	 * @param mixed ...$args Titles
	 * @return static
	 */
	function SetHeader(...$args)
	{
        if((count($args) == 1) && is_array($args[0]))
            $args = $args[0];
		$this->Header()->NewRow($args);
		return $this;
	}
	
	/**
	 * Takes all arguments given and uses each as cell-value to add a footer row.
	 * 
	 * @param mixed ...$args Cell-values
	 * @return static
	 */
	function SetFooter(...$args)
	{
        if((count($args) == 1) && is_array($args[0]))
            $args = $args[0];
		$this->Footer()->NewRow($args);
		return $this;
	}
	
	/**
	 * Same as NewRowGroup($options) but returns $this to allow method chaining.
	 * 
	 * @param array $options See <TBody> for options
	 * @return static
	 */
	function AddNewRowGroup($options=false)
	{
		$this->NewRowGroup($options);
		return $this;
	}
	
	/**
	 * Adds a new row, takes all arguments given and uses each as new data-cell.
	 * 
	 * @param mixed ...$args Values for the new row
	 * @return static
	 */
	function AddNewRow(...$args)
	{
        if((count($args) == 1) && is_array($args[0]))
            $args = $args[0];        
		$this->NewRow($args);
		return $this;
	}
	
	/**
	 * Takes one argument for each (previously set) column
	 * 
	 * possible values: l, r, c (or: left, right, center) as strings
	 * sample $tab->SetAlignment('l','l','c','r') when there are 4+ columns
	 * to skip a column just pass an empty string: $tab->SetAlignment('l','','','r')
	 * @param mixed ...$args Alignement values
	 * @return static
	 */
	function SetAlignment(...$args)
	{
        if((count($args) == 1) && is_array($args[0]))
            $args = array_values($args[0]);
        $this->alignments = $args;
		$this->ColGroup()->SetAlignment($args);
		$this->Header()->SetAlignment($args);
		$this->Footer()->SetAlignment($args);
		foreach( $this->_content as $tbody )
            if(method_exists($tbody, 'SetAlignment'))
                $tbody->SetAlignment($args);
		return $this;
	}
	
	/**
	 * Takes one argument for each (previously set) column
	 * 
	 * possible values: see CellFormat class
	 * sample $tab->SetFormat('int','f2') when there are 2+ columns
	 * to skip a column just pass an empty string: $tab->SetFormat('int','','','f2')
	 * 
	 * @param mixed ...$args Format values
	 * @return static
	 */
	function SetFormat(...$args)
	{
        if((count($args) == 1) && is_array($args[0]))
            $args = $args[0];
        $this->ColFormats = [];
		foreach( $args as $i=>$f )
		{
			if( !$f )
				continue;
			$this->SetColFormat($i, $f);
		}
		return $this;
	}
	
	/**
	 * Just sets the culture.
	 * 
	 * This will be used when value are formatted using a <CellFormat> specified via <Table::SetColFormat> or <Table::SetFormat>
	 * @param CultureInfo $ci <CultureInfo> object speficying the culture
	 * @return static
	 */
	function SetCulture($ci)
	{
		$this->Culture = $ci;
		return $this;
	}
	
	public $_actions = false;
	public $_rowModels = [];
	public $_actionHandler = [];
	public $_sortHandler = false;
	
	/**
	 * Adds a data object to the current row.
	 * 
	 * This will be stored for AJAX acceess
	 * @param mixed $model Data object
	 * @return static
	 */
	function AddDataToRow($model)
	{
		if( !$this->current_row )
			WdfException::Raise("No row added yet");
		$this->current_row->id = $this->current_row->_storage_id;
		$this->_rowModels[$this->current_row->id] = $model;
		return $this;
	}
	
	/**
	 * Gets the model for a specific row id.
	 * 
	 * Note that $row_id is the id of the <Tr> object, not the index in the row listing!
	 * @param string $row_id Id of the <Tr> object
	 * @return mixed The data object
	 */
	function GetRowModel($row_id)
	{
		return $this->_rowModels[$row_id];
	}
	
	/**
	 * Adds an action to the current row.
	 * 
	 * This is in fact a little icon displayed on hovering the row. Clicking on it
	 * will trigger an AJAX action.
	 * @param string $icon Valid <uiControl::Icon>
	 * @param string $label Action label (alt and tootltip text)
	 * @param object|string $handler Object handling the action
	 * @param string $method Objects method that handles the action
	 * @return static
	 */
	function AddRowAction($icon,$label,$handler=false,$method=false)
	{
		if( !$this->_actions )
			$this->_actions = $this->content(new Control('div'))->css('display','none')->css('position','absolute')->addClass('ui-table-actions');
		
		$ra = new Control('span');
		$ra->class = "ui-icon ui-icon-$icon";
		$ra->title = $label;
		$ra->id = $ra->_storage_id;
		$ra->data('action',$icon);
		
		$this->_actions->content( $ra->wrap('div') );
		
		if( $handler && $method )
			$this->_actionHandler[$icon] = array($handler,$method);

		store_object($this);
		return $this;
	}
	
	/**
	 * @internal Handles row action clicks and calls the defined handlers (<Table::AddRowAction>)
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
	
	/**
	 * Sets a sort handler for this table
	 * 
	 * Note: this does not mean that the data can be sorted for display, but that the user may rearrange the rows via mouse drag and drop!
	 * @param object $handler Object handling the drop
	 * @param string $method Method to be called
	 * @return static
	 */
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
	 * @internal Handles the sort-drop event and calls the hanlder (<Table::Sortable>)
	 * @attribute[RequestParam('rows','array',array())]
	 */
	function OnReordered($rows)
	{
		return call_user_func_array($this->_sortHandler,array($this,$rows));
	}
	
	/**
	 * Adds a Pager to the table
	 * 
	 * Will be displayed in the tables footer. See <Table::SetDataCallback> for details how to
	 * add data to a paged table.
	 * @param int $items_per_page Items per page to be displayed
	 * @param int $current_page One (1) based index of current page
	 * @param int $max_pages_to_show Maximum links to pages to be shown
     * @param mixed ...$toomany Catches cases where deprecated method structure is used in call. If present raises an exception.
	 * @return static
	 */
	function AddPager($items_per_page = 15, $current_page=false, $max_pages_to_show=10, ...$toomany)
	{
        if( count($toomany) > 0 )
            WdfException::Raise("Use of obsolete method signature");
        $this->checkCallIsCorrectlyListingWrapped();
        
		$this->TotalItems = 0;
		$this->ItemsPerPage = $items_per_page;
        if($current_page !== false)
            $this->CurrentPage = $current_page;
        elseif( $this->hasSetting('page') )
            $this->CurrentPage = intval(max(1,$this->getSetting('page')));
        elseif(!$this->CurrentPage)
            $this->CurrentPage = 1;
        $this->MaxPagesToShow = $max_pages_to_show;
		store_object($this);
		return $this;
	}
    
    /**
     * Store this tables current page in the Session.
     * 
     * @param string $name A (session-)unique name for the table
     * @param \ScavixWDF\WdfBuffer $storage Optional external Buffer to use a storage container
     * @return static
     */
    function Persist($name, \ScavixWDF\WdfBuffer $storage=null)
    {
        $this->checkCallIsCorrectlyListingWrapped();
        $this->persistance_storage = $storage;
        $this->PersistName = $name;
        if( $this->hasSetting('page') )
            $this->CurrentPage = intval(max(1, $this->getSetting('page')));
        else
        {
            $this->setSetting('page',$this->CurrentPage);
            $this->TriggerOnPageChanged();
        }
        store_object($this);
        return $this;
    }
    
    /**
     * Sets the storage container.
     * 
     * @param \ScavixWDF\WdfBuffer $storage Buffer to be used as storage
     * @return void
     */
    function SetStorage(\ScavixWDF\WdfBuffer $storage)
    {
        $this->checkCallIsCorrectlyListingWrapped();
        $this->persistance_storage = $storage;
    }
    
    /**
     * Resets the current page to be the first.
     * 
     * @return static
     */
    function ResetPager()
    {
        $this->checkCallIsCorrectlyListingWrapped();
        
        if( $this->ItemsPerPage )
           $this->CurrentPage = 1;
        $this->delSetting('page');
        $this->TriggerOnPageChanged();
        return $this;
    }
	
	/**
	 * Sets a handler to be called whenever the table needs data.
	 * 
	 * Use this in conjuction with <Table::AddPager> to generate dynamic data.
	 * @param object $handler Object that will handle the request.
	 * @param string $method Name of the method to be called
	 * @return static
	 */
	function SetDataCallback($handler,$method)
	{
		$this->DataCallback = array($handler,$method);
		return $this;
	}
	
	/**
	 * @internal Will be polled via AJAX to change the page if you defined a pager using <DatabaseTable::AddPager>
	 * @attribute[RequestParam('number','int')]
	 */
	function GotoPage($number)
	{
        $this->checkCallIsCorrectlyListingWrapped();
        $this->CurrentPage = $number;
        $this->setSetting('page', $this->CurrentPage);
        $this->TriggerOnPageChanged();
	}
	
    public $PagerPrefix = false;
	protected function RenderPager()
	{
		$pages = ceil($this->TotalItems / $this->ItemsPerPage);
        $hidden = ($pages < 2) && (!$this->ShowTotalText || ($this->TotalItems == 0));
        
		if( $hidden && !$this->PagerPrefix )
			return;
		
        $this->addClass('haspager');
		$ui = new Control('div');
		$ui->addClass("pager");
        
        if( $this->PagerPrefix )
            $ui->content($this->PagerPrefix);
        if( $hidden )
            return $ui;

		$start = 1;
		while( $pages > $this->MaxPagesToShow && $this->CurrentPage > $start + $this->MaxPagesToShow / 2 )
			$start++;

		if( $start == 2 )
            $ui->content( new Anchor("javascript: wdf.tables.gotoPage('#$this->id',1)","1") );
		elseif( $start > 1 )
		{
			$ui->content( new Anchor("javascript: wdf.tables.gotoPage('#$this->id',1)","1 &laquo;") );
			$ui->content( new Anchor("javascript: wdf.tables.gotoPage('#$this->id',".($this->CurrentPage-1).")","&lsaquo;") );
		}
        
		for( $i=$start; $i<=$pages && $i<($start+$this->MaxPagesToShow); $i++ )
		{
			if( $i == $this->CurrentPage )
				$ui->content("<span class='current'>".($this->Culture !== false ? $this->Culture->FormatInt($i) : $i)."</span>");
			else
				$ui->content(new Anchor("javascript: wdf.tables.gotoPage('#$this->id',$i)", ($this->Culture !== false ? $this->Culture->FormatInt($i) : $i)));
		}
        
		if( $i == $pages )
            $ui->content(new Anchor("javascript: wdf.tables.gotoPage('#$this->id',$i)", ($this->Culture !== false ? $this->Culture->FormatInt($i) : $i)));
        elseif( $i < $pages )
		{
			$ui->content( new Anchor("javascript: wdf.tables.gotoPage('#$this->id',".($this->CurrentPage+1).")","&rsaquo;") );
			$ui->content( new Anchor("javascript: wdf.tables.gotoPage('#$this->id',$pages)","&raquo; ".($this->Culture !== false ? $this->Culture->FormatInt($pages) : $pages)) );
		}
        
        if( $this->ShowTotalText )
            $ui->append("<span class='total'>".sprintf($this->ShowTotalText, ($this->Culture !== false ? $this->Culture->FormatInt($this->TotalItems) : $this->TotalItems))."</span>");

		return $ui;
	}
}
