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
use ScavixWDF\Localization\CultureInfo;
use ScavixWDF\Model\DataSource;
use ScavixWDF\JQueryUI\Dialog\uiDialog;
use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Controls\Table\DatabaseTable;

/**
 * High level listing control.
 *
 * It represents a database table with paging, sorting and more features.
 */
class WdfListing extends Control implements \ScavixWDF\ICallable
{
    const GEAR_TOGGLE_ALL = 'toggle_all';
    const GEAR_CHOOSE_OPTIONAL = 'choose_optional';
    const GEAR_OFF = 'off';

    public $datatype;
    public $datatable;

    public $columns = [];
    public $optional_comluns = [];
    public $alignments = [];
    public $formats = [];
    public $controller;
    public $readonly = false;
    public $javascript = false;
    public $exportable = false;
    public $sortable = true;
    public $persistance_key;
	public $persistkeyextra = '';
    public $details_event = 'details';
    public $details_args = ['uid' => 'uid'];
    public $details_link = false;
    public $default_order = false;

    public $_multiselectname = false;
    public $_multiselectidcolumn = 'id';
    public $_multiactions = [];
    public $_groupMultiActionsTreshold = 0;

    public $columnCallbacks = [];
    public $rowCallbacks = [];
    public $rowDataCallbacks = [];
    public $colClasses = [];

    public $summary = [];
    public $summary_names = [];
    public $ds = false;
	public $ci;

    public $log_sql = false;

    public $gear_mode = 'toggle_all';

    protected $log_sql_done = false;

    protected $onPreRender = false;

    public static $ShowCompleteData = false;
    public static $Exporting = false;           // is set to true in Export() call

	/**
	 * @var uiDatabaseTable
	 */
    public $table;

    /**
     * Constructs a WdfListing control.
     * @param array $args One, two or three arguments. If one, must be Model datatype. If two must be Model datatype and database table, if Three must be Model datatype, UI controller and database table.
     * @return static
     */
	public static function Make(...$args)
	{
        $controller = $datatype = $table = false;
		if( count($args)>3 )
            log_warn(__METHOD__.": Calling with more that 3 arguments is obsolete. Use setter methods for assignment!","Called from ".system_get_caller());

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

    public function __clone()
    {
        if( $this->ci instanceof CultureInfo )
            $this->ci = clone $this->ci;
        if( $this->table instanceof Control )
            $this->table = clone $this->table;
        $this->log_sql_done = false;
        return parent::__clone();
    }

    /**
     * Sets the controller to be used as details target.
     * @param mixed $controller Controller name
     * @return static
     */
	function setController($controller)
	{
		$this->controller = $controller;
		return $this->initDataChanged();
	}

    /**
     * Sets the table to be queried for data.
     * @param mixed $table Table name
     * @return static
     */
	function setTable($table)
	{
		$this->datatable = $table;
		$this->table->DataTable = $table;
		$this->table->Sql = '';
        $this->table->Columns = "`{$this->table->DataTable}`.*";
		return $this->initDataChanged();
	}

    /**
     * Sets the Model datatype.
     * @param mixed $type Model datatype (like MyCustomModel::class)
     * @return static
     */
	function setDataType($type)
	{
		$this->datatype = $type;
		if( !$this->datatable )
			return $this->setTable($this->ds->TableForType($type));
		return $this->initDataChanged();
	}

    /**
     * Sets up an extra key for automatich state storage.
     * @param mixed $extra Some extra seed
     * @return static
     */
	function setPersistKeyExtra($extra)
	{
        if( $this->persistkeyextra == $extra )
            return $this;
		$this->persistkeyextra = $extra;
		return $this->initDataChanged();
	}

    /**
     * @internal
     */
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

        if( $state = \ScavixWDF\Base\Args::request('listing_state',false) )
        {
            self::RestoreSettingsSnapshot($state);
            unset($_REQUEST['listing_state']);
            \ScavixWDF\Base\Args::clearBuffer();
        }

        $this->class = 'listing';

        $this->datatype = $datatype?:\ScavixWDF\Model\CommonModel::class;
        $this->datatable = $table;
        $this->controller = $controller?:current_controller(true);
        $this->sortable = !self::$ShowCompleteData && $sortable;
        $this->persistkeyextra = $persistkeyextra;
        $this->persistance_key = ($datatype.$controller.$table.($sortable?'1':'0').$persistkeyextra);

        $this->ds = DataSource::Get();
        $this->table = uiDatabaseTable::Make($this->ds,$table?false:$datatype,$table)
            ->AssignOnAddHeader($this,'OnAddHeader')
            ->AssignOnAddRow($this,'OnAddRow')
            ->appendTo($this);
        $this->table->ParsingBehaviour = DatabaseTable::PB_NOPROCESSING;
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

    /**
     * @shortcut $this->setOptional($columns_to_remove)->setItemsPerPage($items_per_page)
     */
    function makeSlim($columns_to_remove=[], $items_per_page=10)
    {
        return $this->setOptional($columns_to_remove)
            ->setItemsPerPage($items_per_page);
    }

    protected function logSql($origin=false)
    {
        if( $this->log_sql && !$this->log_sql_done )
        {
            if(!$this->table->ResultSet)
                $this->table->GetData();
            $this->table->ResultSet->LogDebug("{$this->id} SQL ($origin)");
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

    /**
     * Returns the storage buffer.
     *
     * @return \ScavixWDF\WdfBuffer
     */
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

    protected static $SettingsSnapshotRestored = false;

    /**
     * @internal Used in browser-navigation to jump to the correct listing state.
     */
    static function RestoreSettingsSnapshot($id)
    {
        $store = self::Storage();
        $buf = $store->dump();
        if( isset($buf['snapshots']) )
        {
            $snapshots = $buf['snapshots'];
            if( isset($snapshots[$id]) )
            {
                $data = unserialize($snapshots[$id]);
                $store->clear();
                foreach( $data as $k=>$v )
                    $store->set($k,$v);
                $store->set('snapshots',$snapshots);
                self::$SettingsSnapshotRestored = $id;
            }
        }
    }

    /**
     * @internal Used in browser-navigation to jump to the correct listing state.
     */
    static function CreateSettingsSnapshot()
    {
        $store = self::Storage();
        $buf = $store->dump();
        if( isset($buf['snapshots']) )
        {
            $snapshots = $buf['snapshots'];
            unset($buf['snapshots']);
        }
        else $snapshots = [];
        $id = generatePW(12);
        $snapshots[$id] = serialize($buf);
        $store->set('snapshots',$snapshots);
        //log_debug("SNAP $id created",$buf);
        return $id;
    }

    /**
     * @internal Used in automatic setting store
     */
    static function RestoreSettings($data)
    {
        // self::Storage() buffer is linked to session key "listing_storage"
        //log_debug(__METHOD__,$data);
        $_SESSION["listing_storage"] = $data;
        self::Storage()->set('snapshots',[]);
    }

    /**
     * Returns the queried tables name.
     * @return mixed
     */
    function GetTableName()
    {
        if( $this->datatable )
            return $this->datatable;
        $cls = $this->datatype;
        $obj = new $cls();
        return $obj->GetTableName();
    }

    /**
     * @internal
     */
    protected function resetExtension()
    {
        $this->extended = false;
    }

    private $extended = false;
    /**
     * @internal
     */
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
            $sql = str_replace(
                [" SQL_CALC_FOUND_ROWS "," /*SQL_CALC_FOUND_ROWS*/ "],
                [" "," "],
                $this->table->Sql?:$this->table->GetSQL()
            );
            if( count($sumcols)> 0 )
            {
                $formats = [];
                foreach( $this->formats as $n=>$v )
                    $formats[$n] = $v ?: ifavail($this->columnCallbacks,$n);

                $sql = preg_replace("/(\/\*BEG-ORDER\*\/)(.*)(\/\*END-ORDER\*\/)/", '$1$3', $sql);
                $sql = str_replace('SELECT * ', 'SELECT '.implode(',', array_map(function($c) { return "`$c`"; }, $sumcols)).' ', $sql);
                $sums = $this->ds->ExecuteSql("SELECT ".implode(",", array_map(function($c) { return "sum(`$c`) as '{$c}'"; }, $sumcols))." FROM( {$sql} )as x")->current();
                $footer = $this->table->Footer(true);
                $cols = $this->visibleColumns();
                $plain_sum_count = count(array_filter(array_keys($sums), function ($c) { return !avail($this->summary_names, $c); }));
                if ($plain_sum_count > 0)
                {
                    $row = $footer->NewRow(array_fill(0, count($cols), ''));
                    foreach ($sums as $name => $val)
                    {
                        if (avail($this->summary_names, $name))
                            continue;
                        $i = array_search($name, $cols);
                        if ($i !== false)
                        {
                            if ($f = ifavail($formats, $name))
                            {
                                $f = new \ScavixWDF\Controls\Table\CellFormat($f);
                                $val = $f->FormatContent($val, $this->ci);
                            }
                            $row->GetCell($i)->SetContent($val);
                        }
                    }
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
    }

    /**
     * @internal
     */
	function PreRender($args=[])
	{
		if( $this->sortable )
			$this->script("$(document).on('click', '#{$this->id} a[data-sort]', function(e){ e.preventDefault(); wdf.post('{$this->id}/sort',{name:$(this).data('sort')},function(d){ if(d) wdf.tables.update('#{$this->table->id}',d,function(){ wdf.listings.init(); } ); }); });");
		return parent::PreRender($args);
	}

    /**
     * @suppress PHP0406
     * @internal
     */
    function WdfRender()
    {
        if( $this->onPreRender )
            call_user_func($this->onPreRender,$this);

        if( !self::$ShowCompleteData && $this->exportable && ($this->table->ResultSet->Count() > 0) )
        {
            $btnexport = Anchor::Make('javascript:void(0)', tds('BTN_EXPORT','Export'))
                ->attr('onclick', 'wdf.post("'.$this->id.'/export", {target:"'.$this->id.'"})')
                ->addClass('btnexport')
                ->appendTo($this);
            if( $this->table->ResultSet->Count() == 0 )
                $btnexport->css('display', 'none');
        }
        $this->extendInnerTable();

        if( count($this->_multiactions)>0 )
        {
            $div = Control::Make('div')
                ->addClass('multi-actions')
                ->append('<span class="multi-arrow">&#8629;</span>');
            if( $this->table->ResultSet->Count() == 0 )
                $div->css('display', 'none');
            $n = $this->_multiselectname;

            if( $this->_groupMultiActionsTreshold>0 && count($this->_multiactions)>=$this->_groupMultiActionsTreshold )
            {
                $masel = Select::Make()->appendTo($div)
                    ->AddOption('',tds("TXT_CHOOSE_MULTI_ACTION","(choose action)"))
                    ->attr("onchange","$('#{$this->table->id}').overlay(); var d={all_selected:$('[name=\"{$n}_all\"]', '#{$this->table->id}').prop('checked'),multi:'$n',$n:$('[name=\"$n\"]:checked', '#{$this->table->id}').map(function() { return $(this).val(); }).get()}; wdf.post($(this).val(),d,function(r){ $('#{$this->table->id}').overlay('remove'); $('body').append(r); }); $(this).val('');");
                foreach ($this->_multiactions as $label => $url)
                    $masel->AddOption($url, $label);
            }
            else
            {
                foreach ($this->_multiactions as $label => $url)
                    Anchor::Make('javascript:void(0)', $label)
                        ->attr('onclick', "$('#{$this->table->id}').overlay(); var d={all_selected:$('[name=\"{$n}_all\"]', '#{$this->table->id}').prop('checked'),multi:'$n',$n:$('[name=\"$n\"]:checked', '#{$this->table->id}').map(function() { return $(this).val(); }).get()}; wdf.post('$url',d,function(r){ $('#{$this->table->id}').overlay('remove'); $('body').append(r); })")
                        ->appendTo($div);
            }
            $this->table->PagerPrefix = $div;
        }
        $this->store();
        $this->logSql("WdfRender");
        return parent::WdfRender();
    }

    /**
     * Sets the used <CultureInfo>.
     *
     * @param string|\ScavixWDF\Localization\CultureInfo $ci The ci to use.
     * @return static
     */
	function setCulture($ci)
	{
		$this->ci = is_string($ci)?\ScavixWDF\Localization\Localization::getCultureInfo($ci):$ci;
		$this->table->SetCulture($this->ci);
		return $this;
	}

    /**
     * Toggles the sorting feature.
     * @param mixed $on If true, the sorting feature is enabled.
     * @return static
     */
    function setSortable($on=true)
    {
        $this->sortable = $on;
        return $this->initDataChanged();
    }

    /**
     * Toggles the export feature.
     * @param mixed $on If true, the export feature is enabled.
     * @return static
     */
    function setExportable($on=true)
    {
        $this->exportable = $on;
        return $this;
    }

    /**
     * Toggles read-only mode.
     *
     * In read-only mode, the user cannot click on rows to be redirected to 'details' page.
     * @param mixed $on If true, the read-only mode is enabled.
     * @return static
     */
    function setReadonly($on=true)
    {
        $this->readonly = $on;
        return $this;
    }

    /**
     * Toggles SQL logging.
     * @param mixed $on If true, the SQL logging is enabled.
     * @return static
     */
    function setLogSql($on=true)
    {
        $this->log_sql = $on;
        return $this;
    }

    /**
     * Adds a pager to the listing
     * @param mixed $items_per_page Items per page
     * @param mixed $allow_reset_pager Internal: Allows the internal logic to reset the pager to page 1
     * @return static
     */
    function addPager($items_per_page, $allow_reset_pager = true)
	{
		return $this->setItemsPerPage($items_per_page, $allow_reset_pager);
	}

    /**
     * Resets the pager to page 1.
     * @return static
     */
    function ResetPager()
    {
        if( !self::$SettingsSnapshotRestored )
            $this->table->ResetPager();
        return $this;
    }

    /**
     * Sets pagers items per page.
     * @param mixed $perpage Items per page
     * @param mixed $allow_reset_pager Internal: Allows the internal logic to reset the pager to page 1
     * @return static
     */
    function setItemsPerPage($perpage, $allow_reset_pager = true)
    {
        if( !self::$ShowCompleteData )
            $this->table->AddPager($perpage, ($allow_reset_pager && !self::$SettingsSnapshotRestored)?1:false);
        return $this;
    }

    /**
     * Adds a join clause to the underlying query.
     *
     * @param mixed $j Valid SQL JOIN clause
     * @return static
     */
    function addJoin($j)
    {
        if( !preg_match('/^(LEFT|INNER|RIGHT|\s+)+JOIN\s+/',$j) )
            $j = "LEFT JOIN $j";

        if( !$this->table->Join )
            $this->table->Join = "";
        $this->table->Join .= " ".trim($j);
        return $this;
    }

    /**
     * Adds a column based on a subquery.
     *
     * @param mixed $name Column name.
     * @param mixed $label Colulmn label
     * @param mixed $sql SQL statement
     * @param mixed $arguments SQL arguments
     * @param mixed $format Column format or callback.
     * @param mixed $alignment Column alignment.
     * @return static
     */
    function addComplexColumn($name,$label,$sql,$arguments=[],$format=false,$alignment='l')
    {
        return $this
            ->addField("($sql) as '$name'",$arguments)
            ->addColumn($name, $label, $format, $alignment);
    }

    /**
     * Adds a column based on a subquery.
     *
     * @param mixed $sql SQL statement (something like "(select a from tab) as colname" )
     * @param mixed $arguments SQL arguments.
     * @return static
     */
    function addField($sql,$arguments=[])
    {
        if( !$this->table->Columns )
            $this->table->Columns = "*";
//            $this->table->Columns = "`{$this->table->DataTable}`.*";          // why was this added?? let's see where it crashes :-o

        foreach( $arguments as $a )
            $sql = preg_replace('/\?/', (is_numeric($a) ? $a : "'".$this->ds->EscapeArgument($a)."'"), $sql, 1);

        $this->table->Columns .= ",$sql";
        return $this;
    }

    /**
     * Sets columns optional, they will be hidden in the GEAR dropdown.
     * @param array|string $column_name Column name or array of column names.
     * @return static
     */
    function setOptional($column_name)
    {
        foreach( force_array($column_name) as $c )
            $this->optional_comluns[$c] = true;
        return $this;
    }

    /**
     * Adds a column to be rendered.
     *
     * @param mixed $name Column name. If this matches a databse column, those values will be rendered.
     * @param mixed $label Column label.
     * @param mixed $format Column format or callback with signature function($v,$row){ return 'rendered'; }.
     * @param mixed $alignment Column alignment.
     * @return static
     */
    function addColumn($name,$label,$format=false,$alignment='l')
    {
        $this->columns[$name] = $label;
        $this->alignments[$name] = $alignment;
        if( $format && is_callable($format) && !in_array($format, ['duration', 'fixed', 'pre', 'preformatted', 'date', 'time', 'datetime', 'currency', 'int', 'integer', 'percent', 'float', 'double', 'custom']) )
        {
            $this->formats[$name] = false;
            $this->addColCallback([$name], $format);
        }
        else
            $this->formats[$name] = $format;
        return $this;
    }

    /**
     * Set gear mode.
     *
     * "Gear" is the top-right icon that allows advanced listing interaction.
     * @param mixed $gear_mode Once of the GEAR_MODE_* constants.
     * @param mixed $optional_default Default 'visible' value for optional columns.
     * @return static
     */
    function setGearMode($gear_mode, $optional_default=false)
    {
        $this->gear_mode = $gear_mode;
        if( $gear_mode == self::GEAR_CHOOSE_OPTIONAL )
        {
            foreach( $this->optional_comluns as $n=>$d )
                $this->setSetting("hidden_{$n}", $n != $optional_default);
        }
        return $this;
    }

    /**
     * @internal Filter array values to visible values.
     */
    function filterToVisible($key_value_pairs)
    {
        $vis = array_fill_keys($this->visibleColumns(),1);
        return array_intersect_key($key_value_pairs, $vis);
    }

    /**
     * @internal Get visible column names.
     */
    function visibleColumns($names=false)
    {
        $names = $names?:array_keys($this->columns);
        return array_values(array_filter($names,[$this,'isVisible']));
    }

    /**
     * @internal Get index of (visible) column by name.
     */
    function indexOf($name)
    {
        return array_search($name,$this->visibleColumns());
    }

    /**
     * Set a columns label.
     *
     * @param mixed $name Column name
     * @param mixed $label Column label
     * @return static
     */
    function setLabel($name,$label)
    {
        if( !isset($this->columns[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown column '$name'");
        $this->columns[$name] = $label;
        return $this;
    }

    /**
     * Set a columns format and alignment.
     *
     * @param mixed $name Column name
     * @param mixed $format Column format or callback with signature function($v,$row){ return 'rendered'; }.
     * @param mixed $alignment Column alignment.
     * @return static
     */
    function setFormat($name,$format,$alignment='l')
    {
        if( !isset($this->columns[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown column '$name'");
        $this->alignments[$name] = $alignment;
        $this->formats[$name] = $format;
        return $this;
    }

    /**
     * Set a columns alignment.
     * @param mixed $name Column name
     * @param mixed $alignment Column alignment.
     * @return static
     */
    function setAlignment($name,$alignment='l')
    {
        if( !isset($this->columns[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown column '$name'");
        $this->alignments[$name] = $alignment;
        return $this;
    }

    /**
     * Prepends a column to the list of columns.
     * @param mixed $name Column name
     * @param mixed $label Column label
     * @param mixed $format Column format or callback with signature function($v,$row){ return 'rendered'; }.
     * @param mixed $alignment Column alignment.
     * @return static
     */
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

    /**
     * Clears all columns.
     * @return static
     */
    function clearColumns()
    {
        foreach (array_keys($this->columns) as $n)
            if( $n != '__CHECKBOX__' )
                $this->delColumn($n);
        return $this;
    }

    /**
     * Deletes a column.
     * @param mixed $name Column name
     * @return static
     */
    function delColumn($name)
    {
        foreach( force_array($name) as $n )
        {
            $index = array_search($n, array_keys($this->columns));
            if( isset($this->columns[$n]) ) unset($this->columns[$n]);
            if( isset($this->alignments[$n]) ) unset($this->alignments[$n]);
            if( isset($this->formats[$n]) ) unset($this->formats[$n]);
            if( isset($this->columnCallbacks[$n]) ) unset($this->columnCallbacks[$n]);
            if( isset($this->table->ColFormats[$index]) ) unset($this->table->ColFormats[$index]);
            if( isset($this->optional_comluns[$n]) ) unset($this->optional_comluns[$n]);
        }
        return $this;
    }

    /**
     * Moves a column to another position.
     * @param mixed $colToMoveName Column name to move
     * @param mixed $insertBeforeName Column name to insert before
     * @return static
     */
    function moveColumn($colToMoveName, $insertBeforeName)
    {
        $colnames = array_diff(array_keys($this->columns),[$colToMoveName]);
        $i = array_search($insertBeforeName,$colnames);
        array_splice($colnames,$i,0,[$colToMoveName]);
        $buf = array_fill_keys(['columns', 'alignments', 'formats', 'columnCallbacks', 'optional_comluns'], []);
        $ColFormats = [];
        foreach ($colnames as $i=>$n)
        {
            foreach( $buf as $prop=>$data )
                if( isset($this->$prop[$n]) )
                    $buf[$prop][$n] = $this->$prop[$n];
            $index = array_search($n, array_keys($this->columns));
            if( isset($this->table->ColFormats[$index]) )
                $ColFormats[$i] = $this->table->ColFormats[$index];
        }
        foreach ($buf as $prop => $data)
            $this->$prop = $data;
        $this->table->ColFormats = $ColFormats;
        return $this;
    }

    /**
     * @shortcut <Table::AddRowAction>
     */
    function addAction($icon,$label,$controller=false,$method="OnAction")
    {
        $controller = $controller?:$this->controller;
        $this->table->AddRowAction(uiControl::Icon($icon),$label,$controller,$method);
        return $this;
    }

    public $filter = false;

    /**
     * Sets a data filter.
     *
     * Data filters are combined using AND and will be used as WHERE clause.
     * $sql may be a string or a <WdfListingFilter> object.
     * @param mixed $sql SQL query or <WdfListingFilter> object.
     * @param mixed $params Parameters for the SQL query.
     * @param mixed $replace If true all previous setFilter calls will be replaced.
     * @return static
     */
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

    //    log_debug("Listing Conditions",$this->datatype, $this->table->Where, $sql, $this->table->Sql);
        return $this;
    }

    /**
     * @internal Resets the wrapped <DatabaseTable>
     */
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

    /**
     * @internal Sets values for the current <WdfListingFilter>
     */
    function SetFilterValues($resetpager = true)
    {
        if ($this->filter instanceof WdfListingFilter)
        {
            $this->filter->dataFromPost();
            $filtersql = $this->filter->getSql(true);
        }
        else
        {
            $filtersql = $this->table->Where;
            if (starts_iwith(trim($filtersql), 'WHERE '))
                $filtersql = substr(trim($filtersql), 6);
        }

        if(strpos($this->table->Sql, '*BEG ') === false)
        {
            if(strpos($this->table->Sql, '/*END-COLUMNS*/') === false)
                $this->table->Sql = str_replace(' WHERE ', ' WHERE '.$filtersql.' AND ', $this->table->Sql);
            else
            {
                $pos = strpos($this->table->Sql, '/*END-COLUMNS*/');
                if(strpos(substr($this->table->Sql, $pos), ' WHERE ') === false)
                {
                    // if($this->filter instanceof WdfListingFilter)
                        $this->table->Sql = substr($this->table->Sql, 0, $pos).str_replace('/*BEG-ORDER*/', ' HAVING '.$filtersql.'/*BEG-ORDER*/', substr($this->table->Sql, $pos));
                    // else
                    //     $this->table->Sql = substr($this->table->Sql, 0, $pos).str_replace('/*BEG-ORDER*/', ' WHERE '.$filtersql.'/*BEG-ORDER*/', substr($this->table->Sql, $pos));
                }
                else
                    $this->table->Sql = substr($this->table->Sql, 0, $pos).str_replace(' WHERE ', ' WHERE '.$filtersql.' AND ', substr($this->table->Sql, $pos));
            }
        }

        $this->table->Clear();
        if($resetpager)
            $this->table->ResetPager();
        $this->table->header = false;
        $this->table->footer = false;
        $this->resetExtension();

        if ($this->filter instanceof WdfListingFilter)
        {
            $prefix = preg_quote($this->filter->prefix, '/');
            $sql = $this->filter->getSql();
            $this->table->Sql = preg_replace("/(\/\*BEG $prefix\*\/)(.*)(\/\*$prefix END\*\/)/", '$1'.$sql.'$3', $this->table->Sql);
        }

        store_object($this);
        store_object($this->table);

        $this->extendInnerTable();
        return $this->table;
    }

    /**
     * Controls the multi-selectable frature.
     *
     * @param mixed $valuerowid Name of the row that holds the dataset id values (id, uid,...)
     * @param mixed $checkboxname Name of the chackbox controls
     * @param mixed $label If label and URL are given, shortcuts to <WdfListing::addMultiAction>
     * @param mixed $url If label and URL are given, shortcuts to <WdfListing::addMultiAction>
     * @return static
     */
    function setMultiSelectable($valuerowid,$checkboxname,$label='',$url='')
    {
        $this->_multiselectidcolumn = $valuerowid;
        $this->_multiselectname = $checkboxname;
        $this->addClass('multiselect');
        $this->prependColumn('__CHECKBOX__', '');

        if( $label && $url )
            return $this->addMultiAction($label,$url);
        $this->resetExtension();
        return $this;
    }

    /**
     * Adds a multi-action to the listing.
     *
     * @param mixed $label Action label
     * @param mixed $url URL to be called when the action is triggered
     * @return static
     */
    function addMultiAction($label,$url)
    {
        $this->_multiactions[$label] = $url;
        $this->resetExtension();
        return $this;
    }

    /**
     * Disables the multi-selectable frature.
     * @return static
     */
    function setMultiSelectableOff()
    {
        $this->_multiselectname = false;
        $this->_multiactions = [];
        $this->removeClass('multiselect');
        if( isset($this->columns['__CHECKBOX__']) )
            unset($this->columns['__CHECKBOX__']);

        return $this;
    }

    /**
     * Sets the order of the listing data.
     *
     * Multiple setOrder calls will result in multiple ORDER BY clauses.
     * @param mixed $orderby SQL ready ORDER BY clause (may be just a column name)
     * @return static
     */
    function setOrder($orderby)
    {
        $this->default_order = $orderby;

        if( $this->sortable && $this->hasSetting('sort') )
            $this->table->OrderBy = $this->getSetting('sort');
        else
            $this->table->OrderBy = $orderby;
        return $this;
    }

    /**
     * Sets data grouping.
     * @param mixed $groupby SQL ready GROUP BY clause (may be just a column name)
     * @return static
     */
    function setGroupBy($groupby)
    {
        $this->table->GroupBy = $groupby;
        return $this;
    }

    /**
     * Returns the number of found rows.
     *
     * Note that this is not the total number of rows, but the number of rows on the current result page.
     * @return mixed
     */
    function countRows()
    {
        $this->getResultSet();
        try
        {
            return $this->table->ResultSet->Count();
        } catch(\Exception $ex)
        {
            return -1;
        }
    }

    /**
     * @internal Get the underlying <ResultSet>
     */
    function getResultSet()
    {
        if(!$this->table->ResultSet)
            $this->table->GetData();
        return $this->table->ResultSet;
    }

    /**
     * Checks if a column is visible
     * @param mixed $column_name Column name
     * @return bool True is visible, false otherwise
     */
    protected function isVisible($column_name)
    {
        if( $this->gear_mode == self::GEAR_CHOOSE_OPTIONAL )
        {
            if( !ifavail($this->optional_comluns, $column_name) )
                return true;
            // log_debug(__METHOD__, $column_name, $this->getSetting("hidden_{$column_name}", '???'));
            return !$this->getSetting("hidden_{$column_name}",false);
        }
        if( $this->hasSetting("hidden_{$column_name}") )
            return !$this->getSetting("hidden_{$column_name}",false);
        return !ifavail($this->optional_comluns,$column_name);
    }

    /**
     * @internal Header rendering
     */
    function OnAddHeader($table, $row)
    {
        $this->logSql("OnAddHeader");
        $links = $columns = [];
        $choosemode = $this->gear_mode == self::GEAR_CHOOSE_OPTIONAL;

        foreach( $this->columns as $name=>$label )
        {
            $c = [
                'name'=>$name,
                'label'=>$label,
                'visible'=> $this->isVisible($name)
            ];
            if( $name !== '__CHECKBOX__' && (!$choosemode || isset($this->optional_comluns[$name])) )
                $columns[] = $c;
            if( !$c['visible'] )
                continue;

            switch($name)
            {
                case '__CHECKBOX__':
                    $links[] = \ScavixWDF\Controls\Form\CheckBox::Make($this->_multiselectname.'_all')->data('multicheckboxname', $this->_multiselectname)->setValue(isset($row[$this->_multiselectidcolumn]) ? $row[$this->_multiselectidcolumn] : '')->addclass('nomonitor');
                    break;

                default:
                    if($label != '')
                    {
                        if( $this->sortable && $this->table->ResultSet && ($current = $this->table->ResultSet->current()) && array_key_exists($name, $current) )
                            $a = Anchor::Make('javascript:void(0)', $label)->data("sort", $name);
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
        if( $this->gear_mode != self::GEAR_OFF )
            Anchor::Void("<span class='ui-icon ui-icon-gear'></span>")
                ->data('column-state', $columns )
                ->appendTo($wrap);
        $links[] = $wrap;

        $table->Header()->NewRow($links);

        if( $this->colClasses === true )
        {
            $names = array_keys($this->filterToVisible($this->columns));
            $this->colClasses = array_combine($names, $names);
        }
        if( count($this->colClasses)>0 )
        {
            $names = array_keys($this->filterToVisible($this->columns));
            foreach( $table->header->Rows() as $tr )
            {
                foreach( $tr->Cells() as $i=>$td )
                {
                    if( isset($this->colClasses[$names[$i]]) )
                    {
                        $cls = preg_replace('/\{([a-z0-9_]+)\}/i','$1',$this->colClasses[$names[$i]]);
                        $td->addClass($cls);
                    }
                }
            }
        }
    }

    /**
     * @internal Toggles a columns visibility
     * @attribute[RequestParam('name','string',false)]
     */
    function ToggleColumn($name)
    {
        if( $this->gear_mode == self::GEAR_CHOOSE_OPTIONAL )
        {
            if( $this->isVisible($name) ) // do not let user disable, but only choose another
                return $this->table;

            //$this->gear_mode_toggle_default = $name;
            foreach( $this->optional_comluns as $n=>$d )
                $this->setSetting("hidden_{$n}", $n != $name);
        }
        else
        {
            if( $this->isVisible($name) )
            {
                if( ifavail($this->optional_comluns,$name) )
                    $this->delSetting("hidden_{$name}");
                else
                    $this->setSetting("hidden_{$name}",true);
            }
            else
                $this->setSetting("hidden_{$name}",false);
        }

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
        $id = self::CreateSettingsSnapshot();
        return $this->table->data('state-id',$id);
        //return AjaxResponse::Renderable($this->table)->AddScript("wdf.listings.pushState('$id')");
	}

    /**
	 * @internal Will be polled via AJAX to reload listing. Optionally for a specific state.
	 * @attribute[RequestParam('state_id','string','')]
	 */
    function Reload($state_id)
    {
//        log_debug("Reload($state_id)");
        if( $state_id )
        {
            $snap = self::Storage()->get('snapshots',[]);
            if( isset($snap[$state_id]) )
            {
                $store = \ScavixWDF\Wdf::GetBuffer(
                    "listing_storage_snapshot_{$state_id}",
                    unserialize($snap[$state_id])
                    );
            }
        }

        if( isset($store) )
        {
            $page = $store->get("{$this->persistance_key}_page",1);
//            log_debug("SNAP page",$page);
            $this->table->SetStorage($store);
            $this->table->CurrentPage = $page;
            //$this->table->GotoPage($page);
            $this->extendInnerTable();
        }
        else
        {
            $this->table->SetStorage(self::Storage());
            if( $state_id == '-' )
                $this->table->ResetPager();
            $this->extendInnerTable();
            $this->store();
            $store = self::Storage();
        }
        return $this->table;
    }

    /**
     * @internal Switch data sorting
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
        $this->table->Sql = preg_replace("/(\/\*BEG-ORDER\*\/)(.*)(\/\*END-ORDER\*\/)/", '$1'.$this->table->OrderBy.'$3', $this->table->Sql);

        $this->table->Clear()->ResetPager();
        $this->table->header = false;

        $this->setSetting('sort', $this->table->OrderBy);

        $this->extendInnerTable();
        $this->store();
        $this->logSql("Sort");
        return $this->table;
    }

    /**
     * @internal Row rendering
     */
    function OnAddRow($table, $row)
    {
        if( $this->colClasses === true )
        {
            $names = array_keys($this->filterToVisible($this->columns));
            $this->colClasses = array_combine($names, $names);
        }

        $tr = $table->NewRow();

        foreach( $this->rowDataCallbacks as $cb )
            $row = $cb($row);
        $raw_row_data = $row;

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
                $tr->attr('onclick',"wdf.listings.rowclick(event,'$link')");
            }
            else
            {
                $args = [];
                foreach($this->details_args as $name => $valkey)
                    $args[$name] = isset($row[$valkey]) ? $row[$valkey] : $valkey;
                $link = buildQuery($this->controller,$this->details_event,$args);
                $tr->attr('onclick',"wdf.listings.rowclick(event,'$link')");
            }
        }

        foreach( $this->columns as $name=>$label )
        {
            if( !$this->isVisible($name) )
                continue;

            if( isset($this->columnCallbacks[$name]) )
                $row[$name] = $this->columnCallbacks[$name]((isset($row[$name]) ? $row[$name] : null),$row);

            switch($name)
            {
                case '__CHECKBOX__':
                    $tr->NewCell(\ScavixWDF\Controls\Form\CheckBox::Make($this->_multiselectname)->setValue($row[$this->_multiselectidcolumn]));
                    break;
                default:
                    $td = $tr->NewCell(ifavail($row,$name));
                    if( !isset($this->columnCallbacks[$name]) )
                        $td->data('model-col',$name);
                    if( isset($this->colClasses[$name]) )
                    {
                        $cls = preg_replace_callback('/\{([a-z0-9_]+)\}/i',function($m)use($raw_row_data)
                        {
                            return ifavail($raw_row_data,$m[1])?:'';
                        },$this->colClasses[$name]);
                        $td->addClass($cls);
                    }
                    break;
            }
        }

        foreach( $this->rowCallbacks as $cb )
            $cb($row,$tr);

        if( avail($row,'uid') )
            $tr->data('model-uid',$row['uid']);

        return $tr;
    }

    /**
     * Adds a summary column to the table.
     * @param mixed $name Column name.
     * @param mixed $label Column label.
     * @param mixed $format Column format or callback function.
     * @return static
     */
    public function addSummaryCol($name, $label=false, $format=false)
    {
        $this->summary[$name] = 0;
        if( $label )
            $this->summary_names[$name] = [$label,$format?:''];
        return $this;
    }

    /**
     * Sets up the summary feature.
     * @param array $args Array of summary specifications, that is column_name, label, format
     * @return static
     */
    public function setSummary(...$args)
    {
        $this->summary = [];
        $this->summary_names = [];
        foreach( $args as $def )
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

    /**
     * Adds a callback for row preprocessing.
     * @param mixed $callback Callback, signature: function($row,$tr){ return $row; }
     * @return static
     */
    public function addRowCallback($callback)
    {
        $this->rowCallbacks[] = new \ScavixWDF\Base\WdfClosure($callback);
        return $this;
    }

    /**
     * Adds a callback for data preprocessing.
     * @param mixed $callback Callback, signature: function($row){ return $row; }
     * @return static
     */
    public function addRowDataCallback($callback)
    {
        $this->rowDataCallbacks[] = new \ScavixWDF\Base\WdfClosure($callback);
        return $this;
    }

    /**
     * Adds a callback for column rendering.
     *
     * @param array|string $colnames Column name(s)
     * @param callable $callback Callback, signature: function($value, $row){ return 'rendered value'; }
     * @return static
     */
    function addColCallback($colnames,$callback)
    {
        $colnames = force_array($colnames);
        foreach($colnames as $colname)
            $this->columnCallbacks[$colname] = is_string($callback)?$callback:new \ScavixWDF\Base\WdfClosure($callback);
        return $this;
    }

    /**
     * Adds a CSS class to columns.
     *
     * @param array|string $name Column name or array of column names and class-values.
     * @param mixed $class Optional Class-Value, if $name is a string.
     * @return static
     */
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
        if( !is_array($this->colClasses) )
            $this->colClasses = [];
        foreach( $name as $n=>$c )
            if( isset($this->colClasses[$n]) )
                $this->colClasses[$n][] = $c;
            else
                $this->colClasses[$n] = [$c];

        return $this;
    }

    /**
     * Sets a text to be displayed next to the pager.
     *
     * @param mixed $text Text to be displayed.
     * @return static
     */
    function setPagerText($text)
    {
        $this->table->ShowTotalText = $text;
        return $this;
    }

    /**
     * Sets content to be displayed if no data is found.
     *
     * @param mixed $content Content to be displayed.
     * @return static
     */
    function setEmptyContent($content)
    {
        $this->table->contentNoData = $content;
        return $this;
    }

    /**
     * @internal Ajax callback for data export.
     * @attribute[RequestParam('target','string',false)]
     * @attribute[RequestParam('format','string',false)]
     */
    public function Export($target, $format)
    {
        WdfListing::$Exporting = true;

        if(!$format)
        {
            $dlg = uiDialog::Make(
                tds('TITLE_CHOOSE_EXPORT_FORMAT','Choose export format'),
                ['width'=>400,'height'=>230]
            );

            $chosen = $this->getSetting('export_format');
            $sel = Select::Make('format')->appendTo($dlg);
            $sel->CreateLabel(tds('TXT_FILE_FORMAT','Format'))->css('display','block')
                ->prependTo($dlg);

            foreach(['xlsx', 'xls', 'csv'] as $f)
                $sel->CreateOption($f, strtoupper($f), $f==$chosen);

            $dlg->AddCloseButton(tds('BTN_CANCEL','Cancel'));
            $q = buildQuery($target,'export');
            $dlg->AddButton('OK',"wdf.redirect('$q', {format:$('#{$sel->id}').val()}); $('#{$dlg->id}').dialog('close'); ");

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
            if (isset($this->formats['__CHECKBOX__']))
            {
                unset($this->formats['__CHECKBOX__']);
                $n = [];
                foreach ($tab->ColFormats as $k => $v)
                    $n[$k - 1] = $v;
                $tab->ColFormats = $n;
            }
            $tab->Header()->NewRow($this->columns);
            $tab->SetFormat( array_values($this->formats) );
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

        $lst = $this;

        DatabaseTable::$export_def[$format]['fn'] = 'Export_'.$exportfilename.'_'.date("Y-m-d_H-i-s").'.'.$format;

        $tab->Clear();
        $tab->Export($format, function($row) use ($datatype, $lst, $sqlcols)
		{
            foreach( $lst->rowDataCallbacks as $cb )
                $row = $cb($row);

            foreach($sqlcols as $c => $caption)
                if(!isset($row[$c]))
                    $row[$c] = null;

            foreach($row as $k => $val)
            {
                if (isset($lst->columnCallbacks[$k]))
                    $val = $lst->columnCallbacks[$k]($val, $row);
                if( is_string($val) )
                    $val = __translate($val);
                $row[$k] = ($val ? strip_tags(str_replace(['&nbsp;', '<br/>', '<br>'], [' ', ', ', ', '], $val)) : $val);
            }

            foreach( $lst->rowCallbacks as $cb )
                $cb($row,null);

            return $row;
        });

        WdfListing::$Exporting = false;
    }

    /**
     * Allows tuning the multi-action-grouping feature.
     *
     * If there are many multi-actions, they can be grouped into a select control.
     * @param mixed $on If true, grouping will be enabled.
     * @param int $treshold The minimum number of actions to allow grouping.
     * @return static
     */
    function setMultiActionGrouping($on=true, int $treshold=2)
    {
        if ($on)
            $this->_groupMultiActionsTreshold = max(1, $treshold);
        else
            $this->_groupMultiActionsTreshold = 0;
        return $this;
    }
}
