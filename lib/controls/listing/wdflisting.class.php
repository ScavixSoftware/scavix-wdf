<?php
/**
 * Scavix Web Development Framework
 *
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
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace ScavixWDF\Controls\Listing;

use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Base\Control;
use ScavixWDF\Controls\Anchor;
use ScavixWDF\JQueryUI\uiControl;
use ScavixWDF\JQueryUI\uiDatabaseTable;
use ScavixWDF\Model\DataSource;
use ScavixWDF\JQueryUI\Dialog\uiDialog;
use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Controls\Table\DatabaseTable;

class WdfListing extends Control implements \ScavixWDF\ICallable
{    
    var $datatype;
    var $datatable;

    var $columns = [];
    var $optional_comluns = [];
    var $alignments = [];
    var $formats = [];
    var $controller;
    var $readonly = false;
    var $javascript = false;
    var $exportable = false;
    var $sortable = true;
    var $persistance_key;
	var $persistkeyextra = '';
    var $details_event = 'details';
    var $details_args = ['uid' => 'uid'];
    var $details_link = false;
    var $default_order = false;
    
    var $_multiselectname = false;
    var $_multiselectidcolumn = 'id';
    var $_multiactions = [];
    
    var $columnCallbacks = [];
    var $rowCallbacks = [];
    var $rowDataCallbacks = [];
    var $colClasses = [];
    
    var $summary = [];
    var $summary_names = [];
    var $ds = false;
	var $ci;
    
    var $log_sql = false;
    protected $log_sql_done = false;
    
    protected $onPreRender = false;
    
    public static $ShowCompleteData = false;
        
	/**
	 * @var uiDatabaseTable
	 */
    var $table;
	
	public static function Make(...$args)
	{
        $controller = $datatype = $table = false;
		if( count($args)>2 )
		{
			$datatype = $args[0];
			$controller = $args[1];
			$table = $args[2];
		}
        elseif( count($args)>1 )
        {
			$datatype = $args[0];
			$table = $args[1];
        }
        elseif( count($args)>0 )
        {
			$datatype = $args[0];
        }
		return parent::Make($datatype,$controller,$table);
	}
	
	function setController($controller)
	{
		$this->controller = $controller;
		return $this->initDataChanged();
	}

	function setTable($table)
	{
		$this->datatable = $table;
		$this->table->DataTable = $table;
		$this->table->Sql = '';
        $this->table->Columns = "`{$this->table->DataTable}`.*";
		return $this->initDataChanged();
	}
	
	function setDataType($type)
	{
		$this->datatype = $type;
		if( !$this->datatable )
			return $this->setTable($this->ds->TableForType($type));
		return $this->initDataChanged();
	}
	
	function setPersistKeyExtra($extra)
	{
		$this->persistkeyextra = $extra;
		return $this->initDataChanged();
	}	
	
	function initDataChanged()
	{
		$this->persistance_key = $this->datatype.$this->controller.$this->datatable.($this->sortable?'1':'0').$this->persistkeyextra;
		if( !self::$ShowCompleteData )
            $this->table->Persist($this->persistance_key,self::Storage());
		return $this->store();
	}
    
	function __construct($datatype=false, $controller=false, $table=false, $persistkeyextra = false, $sortable=true, $pager_items = 25)
	{
        parent::__construct("div");
        $this->class = 'listing';
        
        $this->datatype = $datatype?:\ScavixWDF\Model\CommonModel::class;
        $this->datatable = $table;
        $this->controller = $controller?:current_controller(true);
        $this->sortable = !self::$ShowCompleteData && $sortable;
        $this->persistance_key = ($datatype.$controller.$table.($sortable?'1':'0').$persistkeyextra);
        
        $this->ds = DataSource::Get();
        $this->table = uiDatabaseTable::Make($this->ds,$table?false:$datatype,$table)
            ->AssignOnAddHeader($this,'OnAddHeader')
            ->AssignOnAddRow($this,'OnAddRow')
            ->appendTo($this);
        $this->table->ParsingBehaviour = \ScavixWDF\Controls\Table\DatabaseTable::PB_NOPROCESSING;
        $this->table->SlimSerialization = true;
        
        if( $table )
            $this->table->Columns = "`{$table}`.*";
        
		if( isset($GLOBALS['CI']) )
			$this->setCulture($GLOBALS['CI']);
		else
			$this->setCulture(\ScavixWDF\Localization\Localization::detectCulture());
		
        if( self::$ShowCompleteData )
        {
            $this->readonly = true;
            delete_object($this->table->id);
        }
        else
        {
            $this->table->Persist($this->persistance_key,self::Storage());
            if( $pager_items )
                $this->addPager($pager_items);
            store_object($this,$this->id);
        }
        
        if( $sortable && $this->hasSetting('sort') )
            $this->table->OrderBy = $this->getSetting('sort');
	}
    
    function makeSlim($columns_to_remove=[], $items_per_page=10)
    {
        return $this->setOptional($columns_to_remove)
            ->setItemsPerPage($items_per_page);
    }
    
    protected function logSql()
    {
        if( $this->log_sql && !$this->log_sql_done )
        {
            $this->table->ResultSet->LogDebug();
            $this->log_sql_done = true;
        }
    }
    
    protected function store()
    {
        if( !self::$ShowCompleteData )
        {
            store_object($this);
            store_object($this->table);
        }
        return $this;
    }
    
    static function Storage()
    {
        return \ScavixWDF\Wdf::GetBuffer("listing_storage")
            ->mapToSession("listing_storage");
    }
    
    protected function getSetting($name,$default=false)
    {
        return self::Storage()->get("{$this->persistance_key}_{$name}", $default);
    }
    
    protected function hasSetting($name)
    {
        return self::Storage()->has("{$this->persistance_key}_{$name}");
    }
    
    protected function setSetting($name,$value)
    {
        self::Storage()->set("{$this->persistance_key}_{$name}", $value);
    }
    
    protected function delSetting($name)
    {
        self::Storage()->del("{$this->persistance_key}_{$name}");
    }
    
    static function RestoreSettings($data)
    {
        // self::Storage() buffer is linked to session key "listing_storage"
        //log_debug(__METHOD__,$data);
        $_SESSION["listing_storage"] = $data;
    }
    
    function GetTableName()
    {
        if( $this->datatable )
            return $this->datatable;
        $cls = $this->datatype;
        $obj = new $cls();
        return $obj->GetTableName();            
    }
    
    private $extended = false;
    function extendInnerTable()
    {
        if( count($this->summary) > 0 && !$this->extended )
        {
            $this->extended = true;
            $sumcols = array_filter(
                array_map(
                    function($n){ return $this->isVisible($n)?$n:false; }, 
                    array_keys($this->summary)
                )
            );
            $sql = str_replace(" SQL_CALC_FOUND_ROWS "," ",$this->table->Sql?:$this->table->GetSQL());
            if( count($sumcols)> 0 )
            {
                $sql = str_replace('SELECT * ', 'SELECT '.implode(',', array_map(function($c) { return "`$c`"; }, $sumcols)).' ', $sql);
                $sums = $this->ds->ExecuteSql("SELECT ".implode(",", array_map(function($c) { return "sum(`$c`) as '{$c}'"; }, $sumcols))." FROM( {$sql} )as x")->current();

                $footer = $this->table->Footer();
                $cols = $this->visibleColumns();
                $row = $footer->NewRow(array_fill(0,count($cols),''));
                foreach( $sums as $name=>$val)
                {
                    $i = array_search($name, $cols);
                    if( $i !== false )
                        $row->GetCell($i)->SetContent($val);
                }
                if( count($this->summary_names) > 0 )
                {
                    $row = Control::Make('div')->addClass('named-summary')->appendTo($this->table);
                    $sum = Control::Make('table')->appendTo($row);
                    foreach( $this->summary_names as $n=>$def )
                    {
                        if( !$this->isVisible($n) )
                            continue;
                        list($l,$f) = $def;
                        if( is_callable($f) )
                        {
                            $sums[$n] = $f($sums[$n]);
                        }
                        elseif( $f )
                        {
                            $f = new \ScavixWDF\Controls\Table\CellFormat($f);
                            $sums[$n] = $f->FormatContent($sums[$n],$this->ci);
                        }
                        $sum->append("<tr><td>$l</td><td>{$sums[$n]}</td></tr>");
                    }
                }
            }
        }
        $this->table->SetAlignment( array_values($this->filterToVisible($this->alignments)) );
        $this->table->SetFormat( array_values($this->filterToVisible($this->formats)) );
        
        //$this->table->header = false;
    }
	
	function PreRender($args=[])
	{
		if( $this->sortable )
			$this->script("$(document).on('click', '#{$this->id} a[data-sort]', function(e){ e.preventDefault(); wdf.post('{$this->id}/sort',{name:$(this).data('sort')},function(d){ if(d) $('#{$this->table->id}').updateTable(d); }); });");
		return parent::PreRender($args);
	}
    
    function WdfRender() 
    {
        if( $this->onPreRender )
            call_user_func($this->onPreRender,$this);
		
        if( !self::$ShowCompleteData && $this->exportable && ($this->table->ResultSet->Count() > 0) )
            Anchor::Make('javascript:void(0)', tds('BTN_EXPORT','Export'))->attr('onclick', 'wdf.post("'.$this->id.'/export", {target:"'.$this->id.'"})')->addClass('btnexport')->appendTo($this);
        
        $this->extendInnerTable();
        
        if( count($this->_multiactions)>0 )
        {
            $div = Control::Make('div')
                ->addClass('multi-actions')
                ->append('<span class="multi-arrow">&#8629;</span>');
            $n = $this->_multiselectname;
            foreach( $this->_multiactions as $label=>$url )
                Anchor::Make('javascript:void(0)',$label)
                    ->attr('onclick',"$('#{$this->table->id}').overlay(); var d={all_selected:$('[name=\"{$n}_all\"]').prop('checked'),multi:'$n',$n:$('[name=\"$n\"]:checked').map(function() { return $(this).val(); }).get()}; wdf.post('$url',d,function(r){ $('#{$this->table->id}').overlay('remove'); $('body').append(r); })")
                    ->appendTo($div);
                    
            $this->table->PagerPrefix = $div;
        }        
        $this->store();
        return parent::WdfRender();
    }
    
	function setCulture($ci)
	{
		$this->ci = is_string($ci)?\ScavixWDF\Localization\Localization::getCultureInfo($ci):$ci;
		$this->table->SetCulture($this->ci);
		return $this;
	}
	
    function setSortable($on=true)
    {
        $this->sortable = $on;
        return $this->initDataChanged();
    }
    
    function setReadonly($on=true)
    {
        $this->readonly = $on;
        return $this;
    }

    function setLogSql($on=true)
    {
        $this->log_sql = $on;
        return $this;
    }
    
    function addPager($items_per_page)
	{
		return $this->setItemsPerPage($items_per_page);
	}
	
    function setItemsPerPage($perpage)
    {
        if( !self::$ShowCompleteData )
            $this->table->AddPager($perpage);
        return $this;
    }
    
    function addJoin($j)
    {
        if( !preg_match('/^(LEFT|INNER|RIGHT|\s+)+JOIN\s+/',$j) )
            $j = "LEFT JOIN $j";
        
        if( !$this->table->Join )
            $this->table->Join = "";
        $this->table->Join .= " ".trim($j);
        return $this;
    }
    
    function addComplexColumn($name,$label,$sql,$arguments=[])
    {
        return $this
            ->addField("($sql) as '$name'",$arguments)
            ->addColumn($name, $label);
    }
    
    function addField($sql,$arguments=[])
    {
        if( !$this->table->Columns )
            $this->table->Columns = "`{$this->table->DataTable}`.*";
        
        foreach( $arguments as $a )
            $sql = preg_replace('/\?/', (is_numeric($a) ? $a : "'".$this->ds->EscapeArgument($a)."'"), $sql, 1);
        
        $this->table->Columns .= ",$sql";
        return $this;
    }
    
    function setOptional($column_name)
    {
        foreach( force_array($column_name) as $c )
            $this->optional_comluns[$c] = true;
        return $this;
    }
    
    function addColumn($name,$label,$format=false,$alignment='l')
    {
        $this->columns[$name] = $label;
        $this->alignments[$name] = $alignment;
        if( $format && (is_callable($format) && !is_string($format)) )
        {
            $this->formats[$name] = false;
            $this->addColCallback([$name], $format);
        }
        else
            $this->formats[$name] = $format;
        return $this;
    }
    
    function filterToVisible($key_value_pairs)
    {
        $vis = array_fill_keys($this->visibleColumns(),1);
        return array_intersect_key($key_value_pairs, $vis);
    }
    
    function visibleColumns($names=false)
    {
        $names = $names?:array_keys($this->columns);
        return array_values(array_filter($names,[$this,'isVisible']));
    }
    
    function indexOf($name)
    {
        return array_search($name,$this->visibleColumns());
    }
    
    function setLabel($name,$label)
    {
        if( !isset($this->columns[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown column '$name'");
        $this->columns[$name] = $label;
        return $this;
    }
    
    function setFormat($name,$format,$alignment='l')
    {
        if( !isset($this->columns[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown column '$name'");
        $this->alignments[$name] = $alignment;
        $this->formats[$name] = $format;
        return $this;
    }
    
    function setAlignment($name,$alignment='l')
    {
        if( !isset($this->columns[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown column '$name'");
        $this->alignments[$name] = $alignment;
        return $this;
    }
    
    function prependColumn($name,$label,$format=false,$alignment='l')
    {
        $this->columns = array_reverse($this->columns, true);
        $this->alignments = array_reverse($this->alignments, true);
        $this->formats = array_reverse($this->formats, true);
        $this->addColumn($name,$label,$format,$alignment);
        $this->formats = array_reverse($this->formats, true);
        $this->alignments = array_reverse($this->alignments, true);
        $this->columns = array_reverse($this->columns, true);
        if($this->table->ColFormats)
        {
            $formats = [];
            foreach($this->table->ColFormats as $c => $fmt)
                $formats[$c+1] = $fmt;
            $this->table->ColFormats = $formats;
        }
        return $this;
    }
    
    function delColumn($name)
    {
        foreach( force_array($name) as $n )
        {
            $index = array_search($n, array_keys($this->columns));
            if( isset($this->columns[$n]) ) unset($this->columns[$n]);
            if( isset($this->alignments[$n]) ) unset($this->alignments[$n]);
            if( isset($this->formats[$n]) ) unset($this->formats[$n]);
            if( isset($this->columnCallbacks[$n]) ) unset($this->columnCallbacks[$n]);
            if( isset($this->ColFormats[$index]) ) unset($this->ColFormats[$index]);
        }
        return $this;
    }
    
    function addAction($icon,$label,$controller=false,$method="OnAction")
    {
        $controller = $controller?:$this->controller;
        $this->table->AddRowAction(uiControl::Icon($icon),$label,$controller,$method);
        return $this;
    }
    
    var $filter = false;
    function setFilter($sql, $params = [], $replace=false)
    {
        if( $sql instanceof WdfListingFilter )
        {
            $this->filter = $sql;
            $sql->setListing($this);
            $sql = $sql->getSql(true);
        }
        if( !trim($sql) )
            return $this;
        
        if($this->datatype)
        {        
            $sql = \ScavixWDF\Model\ResultSet::MergeSql($this->ds, $sql, $params);
            $oldwhere = $this->table->Where;
            if( $this->table->Where && !$replace )
                $this->table->Where .= "AND ($sql)";
            else
                $this->table->Where = "($sql)";
            if($oldwhere != $this->table->Where)
                $this->SetFilterValues(false);
        }
        elseif(strpos($this->table->Sql, '*BEG ') === false)
        {
            $oldsql = $this->table->Sql;
            $this->table->Sql = str_replace(' WHERE ', ' WHERE '.$sql.' AND ', $this->table->Sql);
            if($oldsql != $this->table->Sql)
                $this->SetFilterValues(false);
        }
        
//        log_debug("Listing Conditions",$this->datatype, $this->table->Where, $sql, $this->table->Sql);
        return $this;
    }
    
    function Reset()
    {
        $s = self::Storage();
        foreach( $s->keys() as $k )
            if( starts_with($k,"{$this->persistance_key}_") )
                $s->del($k);
        
        if(avail($this, 'default_order'))
        {
            $this->setSetting('sort', $this->default_order);
            $this->setOrder($this->default_order);
        }
		else
			$this->table->OrderBy = '';
        
		$this->table->Sql = "";
        return $this->SetFilterValues(true);
    }
    
    function SetFilterValues($resetpager = true)
    {
        if(!$this->filter instanceof WdfListingFilter)
            return;
        
        $this->filter->dataFromPost();
        
        if(strpos($this->table->Sql, '*BEG ') === false)
            $this->table->Sql = str_replace(' WHERE ', ' WHERE '.$this->filter->getSql(true).' AND ', $this->table->Sql);
        
        $this->table->Clear();
        if($resetpager)
            $this->table->ResetPager();
        $this->table->header = false;
        $this->table->footer = false;
        
        $prefix = preg_quote( $this->filter->prefix,'/');
        $sql = $this->filter->getSql();
        $this->table->Sql = preg_replace("/(\/\*BEG $prefix\*\/)(.*)(\/\*$prefix END\*\/)/", '$1'.$sql.'$3', $this->table->Sql);
        //log_debug($prefix, $sql, $this->table->Sql);

        store_object($this);
        store_object($this->table);
        
        $this->extendInnerTable();
        return $this->table;
    }
    
    function setMultiSelectable($valuerowid,$checkboxname,$label='',$url='')
    {
        $this->_multiselectidcolumn = $valuerowid;
        $this->_multiselectname = $checkboxname;
        $this->addClass('multiselect');
        $this->prependColumn('__CHECKBOX__', '');
        
        if( $label && $url )
            return $this->addMultiAction($label,$url);
        return $this;
    }
    
    function addMultiAction($label,$url)
    {
        $this->_multiactions[$label] = $url;
        return $this;
    }
    
    function setOrder($orderby)
    {
        $this->default_order = $orderby;
        
        if( $this->sortable && $this->hasSetting('sort') )
            $this->table->OrderBy = $this->getSetting('sort');
        else
            $this->table->OrderBy = $orderby;
        return $this;
    }
    
    function setGroupBy($groupby)
    {
        $this->table->GroupBy = $groupby;
        return $this;
    }
    
    function countRows()
    {
        if(!$this->table->ResultSet)
            $this->table->GetData();
        return $this->table->ResultSet->Count();
    }
    
    function getResultSet()
    {
        if(!$this->table->ResultSet)
            $this->table->GetData();
        return $this->table->ResultSet;
    }
    
    protected function isVisible($column_name)
    {
        if( $this->hasSetting("hidden_{$column_name}") )
            return !$this->getSetting("hidden_{$column_name}",false);
        return !ifavail($this->optional_comluns,$column_name);
    }
    
    function OnAddHeader($table, $row)
    {
        $links = $columns = [];
        
        foreach( $this->columns as $name=>$label )
        {
            $c = [
                'name'=>$name,
                'label'=>$label,
                'visible'=> $this->isVisible($name)
            ];
            if( $name !== '__CHECKBOX__' )
                $columns[] = $c;
            if( !$c['visible'] )
                continue;
            
            switch($name)
            {
                case '__CHECKBOX__':
                    $links[] = \ScavixWDF\Controls\Form\Checkbox::Make($this->_multiselectname.'_all')->setData('multicheckboxname', $this->_multiselectname)->setValue(isset($row[$this->_multiselectidcolumn]) ? $row[$this->_multiselectidcolumn] : '')->addclass('nomonitor');
                    break;
                    
                default:
                    if($label != '')
                    {
                        if( $this->sortable && $this->table->ResultSet && array_key_exists($name, $this->table->ResultSet->current()) )
                            $a = Anchor::Make('javascript:void(0)', $label)->setData("sort", $name);
                        else
                            $a = Control::Make('span')->append($label);
                        if( preg_match('/\b'.preg_quote($name,'/').'`? DESC\b/i',$table->OrderBy) )
                            $a->addClass('cursort desc');
                        elseif( preg_match('/\b'.preg_quote($name,'/').'\b/i',$table->OrderBy) )
                            $a->addClass('cursort asc');
                        $links[] = $a;
                    }
                    else
                        $links[] = '';
                    break;
            }
        }
        $wrap = Control::Make()->append(array_pop($links))->append('&nbsp;');
        Anchor::Void("<span class='ui-icon ui-icon-gear'></span>")->setData('column-state', $columns)->appendTo($wrap);
        
        $links[] = $wrap;
        
        $table->Header()->NewRow($links);
        
        if( $this->colClasses === true )
        {
            $names = array_keys($this->columns);
            $this->colClasses = array_combine($names, $names);
        }
        if( count($this->colClasses)>0 )
        {
            $names = array_keys($this->columns);
            foreach( $table->header->Rows() as $tr )
                foreach( $tr->Cells() as $i=>$td )
                    if( isset($this->colClasses[$names[$i]]) )
                        $td->addClass($this->colClasses[$names[$i]]);
        }
    }
    
    /**
     * @attribute[RequestParam('name','string',false)]
     */
    function ToggleColumn($name)
    {
        //log_debug(__METHOD__,$name);
        if( $this->isVisible($name) )
        {
            if( ifavail($this->optional_comluns,$name) )
                $this->delSetting("hidden_{$name}",true);
            else
                $this->setSetting("hidden_{$name}",true);
        }
        else
            $this->setSetting("hidden_{$name}",false);
        
        $this->table->header = false;
        $this->extendInnerTable();
        $this->store();
        return $this->table;
    }
    
    /**
	 * @internal Will be polled via AJAX to change the page if you defined a pager using <DatabaseTable::AddPager>
	 * @attribute[RequestParam('number','int')]
	 */
	function GotoPage($number)
	{
        $this->table->SetStorage(self::Storage());
        $this->table->GotoPage($number);
        $this->extendInnerTable();
        $this->store();
        return $this->table;
	}
    
    function Reload()
    {
        $this->table->SetStorage(self::Storage());
        $this->extendInnerTable();
        $this->store();
        return $this->table;
    }
    
    /**
     * @attribute[RequestParam('name','string',false)]
     */
    function Sort($name)
    {
        if( !$name || !isset($this->columns[$name]) ) 
            return AjaxResponse::None();
        
        if(preg_match('/ORDER\sBY[\s\'"`]+'.$name.'[\s\'"`]+DESC/i',$this->table->OrderBy))
            $this->table->OrderBy = ($this->default_order ? ' ORDER BY '.str_replace(' DESC', '', $this->default_order) : '');
        else
            $this->table->OrderBy = ' ORDER BY `'.$name.'`'.(preg_match('/ORDER\sBY[\s\'"`]+'.$name.'[\s\'"`]*$/i',$this->table->OrderBy)?' DESC':'');
        $this->table->Sql = "";//preg_replace('/ORDER BY(.*)/i', $this->table->OrderBy, $this->table->Sql);
        
        $this->table->Clear()->ResetPager();
        $this->table->header = false;
        
        $this->setSetting('sort', $this->table->OrderBy);
        
        $this->extendInnerTable();
        $this->store();
        return $this->table;
    }
    
    function OnAddRow($table, $row)
    {
        if( $this->colClasses === true )
        {
            $names = array_keys($this->columns);
            $this->colClasses = array_combine($names, $names);
        }
        
        $tr = $table->NewRow();

        foreach( $this->rowDataCallbacks as $cb )
            $row = $cb($row);
        
        if( !$this->readonly )
        {
            if( $this->javascript )
            {
                if( is_string($this->javascript) )
                {
                    $js = preg_replace_callback('/\{([a-z0-9_]+)\}/i',function($m)use($row)
                    {
                        return ifavail($row,$m[1])?:'';
                    },$this->javascript);
                    $tr->attr('onclick',"$js");
                }
                else
                    $tr->attr('onclick',"{$this->controller}.call(this,".htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8').")");
            }
            elseif( $this->details_link )
            {
                $link = preg_replace_callback('/\{([a-z0-9_]+)\}/i',function($m)use($row)
                {
                    return ifavail($row,$m[1])?:''; //$m[0];
                },$this->details_link);
                $tr->attr('onclick',"listing_rowclick(event,'$link')");
            }
            else
            {
                $args = [];
                foreach($this->details_args as $name => $valkey)
                    $args[$name] = isset($row[$valkey]) ? $row[$valkey] : $valkey;
                $link = buildQuery($this->controller,$this->details_event,$args);
                $tr->attr('onclick',"listing_rowclick(event,'$link')");
            }
        }
        
        foreach( $this->columns as $name=>$label )
        {
            if( !$this->isVisible($name) )
                continue;
            
            if( isset($this->columnCallbacks[$name]) )
                $row[$name] = $this->columnCallbacks[$name](ifavail($row,$name),$row);
            
            switch($name)
            {
                case '__CHECKBOX__':
                    $tr->NewCell(\ScavixWDF\Controls\Form\Checkbox::Make($this->_multiselectname)->setValue($row[$this->_multiselectidcolumn]));
                    break;
                default:
                    $td = $tr->NewCell(ifavail($row,$name));
                    if( !isset($this->columnCallbacks[$name]) )
                        $td->setData('model-col',$name);
                    if( isset($this->colClasses[$name]) )
                        $td->addClass($this->colClasses[$name]);
                    break;
            }
        }
            
        foreach( $this->rowCallbacks as $cb )
            $cb($row,$tr);
        
        if( avail($row,'uid') )
            $tr->setData('model-uid',$row['uid']);
        
        return $tr;
    }
    
    public function addSummaryCol($name, $label=false, $format=false)
    {
        $this->summary[$name] = 0;
        if( $label )
            $this->summary_names[$name] = [$label,$format?:''];
        return $this;
    }
    public function setSummary()
    {
        $this->summary = [];
        $this->summary_names = [];
        foreach( func_get_args() as $def )
        {
            if( is_array($def) )
            {
                if( count($def)<3 ) $def[] = '';
                list($n,$l,$f) = $def;
                $this->summary[$n] = 0;
                $this->summary_names[$n] = [$l,$f];
            }
            else
                $this->summary[$def] = 0;
        }
        store_object($this);
        return $this;
    }
    
    public function addRowCallback($callback)
    {
        $this->rowCallbacks[] = new \ScavixWDF\Base\WdfClosure($callback);
        return $this;
    }
    
    public function addRowDataCallback($callback)
    {
        $this->rowDataCallbacks[] = new \ScavixWDF\Base\WdfClosure($callback);
        return $this;
    }
    
    function addColCallback($colnames,$callback)
    {
        $colnames = force_array($colnames);
        foreach($colnames as $colname)
            $this->columnCallbacks[$colname] = is_string($callback)?$callback:new \ScavixWDF\Base\WdfClosure($callback);
        return $this;
    }
    
    function addColClass($name,$class=false)
    {
        if( !is_array($name) )
        {
            if( $name === true )
            {
                $this->colClasses = true;
                return $this;
            }
            $name = [$name=>$class];
        }
        foreach( $name as $n=>$c )
            $this->colClasses[$name][] = $c;
        
        return $this;
    }
    
    function setPagerText($text)
    {
        $this->table->ShowTotalText = $text;
        return $this;
    }
    
    /**
     * @attribute[RequestParam('target','string',false)]
     * @attribute[RequestParam('format','string',false)]
    */
    public function Export($target, $format)
    {
        if(!$format)
        {
            $dlg = uiDialog::Make('TITLE_CHOOSE_EXPORT_FORMAT', array('width'=>400,'height'=>230)); //->append($frm);
         
            $chosen = $this->getSetting('export_format');
            $sel = Select::Make('format')->attr('title', 'TXT_FILE_FORMAT')->appendTo($dlg);
            foreach(['xlsx', 'xls', 'csv'] as $f)
                $sel->CreateOption($f, strtoupper($f), $f==$chosen);
            
            $dlg->AddCloseButton('BTN_CANCEL');
            $dlg->AddButton('OK',"wdf.redirect('{$target}/export', {format:$('#{$sel->id}').val()}); $('#{$dlg->id}').dialog('close'); ");
            
            return $dlg;
        }
        
        $this->setSetting('export_format', $format);
        
        $tab = clone $this->table;
        
        $datatype = ($this->export_datatype ?: $this->datatype);
        
        if($this->export_columns)
        {
            $tab->Columns = $this->export_columns;

            $origcolumns = $this->columns;
            $this->columns = [];
            foreach($this->export_columns as $caption => $col)
            {
                if(is_numeric($caption))
                {
                    if(isset($origcolumns[$col]))
                        $this->columns[$col] = $origcolumns[$col];
                    elseif(translation_string_exists('TXT_'.$col))
                        $this->columns[$col] = getString('TXT_'.$col);
                    else
                        $this->columns[$col] = $col;
                }
                else
                    $this->columns[$col] = $caption;
            }
            $tab->Columns = array_unique($tab->Columns);
            $tab->Header()->NewRow($this->columns);
            $tab->ColFormats = [];
        }
        else
        {
            if(isset($this->columns['__CHECKBOX__']))
                unset($this->columns['__CHECKBOX__']);
            $tab->Header()->NewRow($this->columns);
        }
        
        $sqlcols = $this->columns;
        foreach($this->columns as $c => $caption)
        {
            if(preg_match('/ AS '.$c.'[,) ]/i', $tab->Sql))
                unset($sqlcols[$c]);
        }
        $tab->Columns = array_unique(array_keys($this->columns));
        
        $exportfilename = str_replace('Model', '', $datatype).'s';
                
        if(isset($sqlcols['__CHECKBOX__']))
            unset($sqlcols['__CHECKBOX__']);
        
        DatabaseTable::$export_def[$format]['fn'] = 'Export_'.$exportfilename.'_'.date("Y-m-d_H-i-s").'.'.$format;
        $tab->Clear();
        $tab->Export($format, function($row) use ($datatype)
		{
            foreach($row as $k => $val)
                $row[$k] = strip_tags(str_replace(['&nbsp;', '<br/>', '<br>'], [' ', ', ', ', '], $val));
            
            return $row;
        });
    }
}
