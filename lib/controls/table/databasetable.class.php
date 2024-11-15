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

use PDO;
use ScavixWDF\ICallable;
use ScavixWDF\Localization\CultureInfo;

default_string("TXT_NO_DATA_FOUND","no data found");

/**
 * Allows to easily integrate database tables into UI.
 *
 */
class DatabaseTable extends Table implements ICallable
{
	const PB_NOPROCESSING = 0x00;
	const PB_STRIPHTML = 0x01;
	const PB_HTMLSPECIALCHARS = 0x02;

	public $DataSource = false;
	public $ResultSet = false;
	public $DataTable = false;
	public $Sql = false;
	public $CacheExecute = false;

	public $Columns = false;
	public $Join = false;
	public $Where = false;
	public $GroupBy = false;
	public $Having = false;
	public $OrderBy = false;
	public $Limit = false;

	public $OnAddHeader = false;
	public $OnAddRow = false;
	public $OnAddFooter = false;
	public $ExecuteSqlHandler = false;

	public $noDataAsRow = false;
	public $contentNoData = "TXT_NO_DATA_FOUND";

	public $ParsingBehaviour = self::PB_HTMLSPECIALCHARS;

    public $SlimSerialization = false;

	/**
	 * @param \ScavixWDF\Model\DataSource $datasource DataSource to use
	 * @param string $datatype Datatype to be rendered
	 * @param string $datatable Data tyble to be rendered
	 */
	function __construct($datasource,$datatype=false,$datatable=false)
	{
		parent::__construct();
		$this->DataSource = $datasource;

		if( $datatype )
			$this->DataTable = $this->DataSource->TableForType($datatype);
		elseif( $datatable )
			$this->DataTable = $datatable;

		store_object($this);
	}

    function __sleep()
    {
        $res = get_object_vars($this);
        unset($res['persistance_storage']);
        if( $this->SlimSerialization )
        {
            unset($res['header']);
            unset($res['footer']);
            unset($res['colgroup']);
            unset($res['current_row_group']);
            unset($res['current_row']);
            unset($res['current_cell']);

            unset($res['_content']);
        }
        return array_keys($res);
    }

	public function __clone()
	{
		if($this->ResultSet instanceof \ScavixWDF\Model\ResultSet)
			$this->ResultSet = clone $this->ResultSet;
		return parent::__clone();
	}

	private function ExecuteSql($sql,$prms=[])
	{
        if( $this->logIfSlow )
            $logtimer = start_timer("[".\ScavixWDF\Model\ResultSet::MergeSql($this->DataSource,$sql,$prms)."]");

		if( $this->ExecuteSqlHandler )
			call_user_func($this->ExecuteSqlHandler,$this,$sql,$prms);
		else
		{
			if( $this->ItemsPerPage )
            {
				$this->ResultSet = $this->DataSource->PageExecute($sql,$this->ItemsPerPage,$this->CurrentPage,$prms);
                if(($this->ResultSet->Count() == 0) && ($this->CurrentPage > 1))
                {
                    // no items on current page, so reset to first page
                    $this->ResetPager();
                    $this->ResultSet = $this->DataSource->PageExecute($sql,$this->ItemsPerPage,$this->CurrentPage,$prms);
                }
            }
			else
			{
				if( $this->CacheExecute )
					$this->ResultSet = $this->DataSource->CacheExecuteSql($sql,$prms);
				else
					$this->ResultSet = $this->DataSource->ExecuteSql($sql,$prms);
			}
		}
        $this->TotalItems = $this->ResultSet?$this->ResultSet->GetpagingInfo('total_rows'):0;
		if( $this->DataSource->ErrorMsg() )
			log_error(get_class($this).": ".$this->DataSource->ErrorMsg());
        elseif( isset($logtimer) )
            finish_timer($logtimer,$this->logIfSlow);
	}

	/**
	 * @override
	 */
	function Clear()
	{
		$this->ResultSet = false;
        $this->TotalItems = 0;
		return parent::Clear();
	}

	/**
	 * @internal Builds the SQL query and executed it
	 */
	final function GetData()
	{
//        log_debug(__METHOD__,$this->Sql);
		if( !$this->Sql )
			$this->Sql = $this->GetSQL();
//        $this->Sql = str_ireplace('ORDER BY ORDER BY',"ORDER BY", $this->Sql);
//        log_debug(__METHOD__,$this->Sql);
		$this->Clear();
		$this->ExecuteSql($this->Sql);
        if($this->ResultSet->HadError() && $this->OrderBy)
        {
            $this->OrderBy = false;
            $this->Sql = $this->GetSQL();
            $this->ExecuteSql($this->Sql);
        }
	}
    /**
     * Returns the SQL statement used in this table.
     *
     * @return string The SQL statement
     */
    final function GetSQL()
    {
        if( !$this->Columns )
            $this->Columns = $this->GetColumns();

        if( !$this->Join )
            $this->Join = $this->GetJoin();

        if( !$this->Where )
            $this->Where = $this->GetWhere();

        if( !$this->GroupBy )
            $this->GroupBy = $this->GetGroupBy();

        if( !$this->Having )
            $this->Having = $this->GetHaving();

        if( !$this->OrderBy )
            $this->OrderBy = $this->GetOrderBy();

        if( !$this->Limit )
            $this->Limit = $this->GetLimit();

        if( is_array($this->Columns) )
        {
            foreach( $this->Columns as $k=>$v )
                if( !preg_match('/[^a-zA-Z0-9]/',$v) )
                    $this->Columns[$k] = "`$v`";
        }

        $this->Columns = is_array($this->Columns)?implode(",",$this->Columns):$this->Columns;
        $this->Join = $this->Join?$this->Join:"";
        $this->Where = $this->Where?$this->Where:"";
        $this->GroupBy = $this->GroupBy?$this->GroupBy:"";
        $this->OrderBy = $this->OrderBy?$this->OrderBy:"";

        if( $this->Where && !preg_match('/^\s*WHERE\s+/',$this->Where) ) $this->Where = " WHERE ".$this->Where;
        if( $this->Join && !preg_match('/^(LEFT|INNER|RIGHT|\s*)+JOIN\s+/',$this->Join) ) $this->Join = " LEFT JOIN ".$this->Join;
        if( $this->GroupBy && !preg_match('/^\s*GROUP\sBY\s+/',$this->GroupBy) ) $this->GroupBy = " GROUP BY ".$this->GroupBy;
        if( $this->Having && !preg_match('/^\s*HAVING\s+/',$this->Having) ) $this->Having = " HAVING ".$this->Having;
        if( $this->OrderBy && !preg_match('/^\s*ORDER\sBY\s+/',$this->OrderBy) ) $this->OrderBy = " ORDER BY ".$this->OrderBy;
        if( $this->Limit && !preg_match('/^\s*LIMIT\s+/',$this->Limit) ) $this->Limit = " LIMIT ".$this->Limit;

        if( $this->ItemsPerPage && !$this->HidePager )
            $sql = "SELECT SQL_CALC_FOUND_ROWS @fields@ FROM @table@@join@@where@@groupby@@having@@orderby@@limit@";
        else
            $sql = "SELECT @fields@ FROM @table@@join@@where@@groupby@@having@@orderby@@limit@";
        $sql = str_replace("@fields@","/*BEG-COLUMNS*/{$this->Columns}/*END-COLUMNS*/",$sql);
        $sql = str_replace("@table@","`".$this->DataTable."`",$sql);
        $sql = str_replace("@join@",$this->Join,$sql);
        $sql = str_replace("@where@",$this->Where,$sql);
        $sql = str_replace("@groupby@",$this->GroupBy,$sql);
        $sql = str_replace("@having@",$this->Having,$sql);
        $sql = str_replace("@orderby@","/*BEG-ORDER*/{$this->OrderBy}/*END-ORDER*/",$sql);
        $sql = str_replace("@limit@","/*BEG-LIMIT*/$this->Limit/*END-LIMIT*/",$sql);

        return $sql;
    }

	/**
	 * Allows to override the default execute method
	 *
	 * This will allow you to integrate your own execution handler
	 * @param object $handler Object containing the handler method
	 * @param string $function Name of handler method
	 * @return void
	 */
	function OverrideExecuteSql(&$handler,$function)
	{
		$this->ExecuteSqlHandler = array($handler,$function);
	}

	/**
	 * Allows to assign your own handler to the AddHeader function
	 *
	 * Sometimes you do not want to inherit from this, but create a table and assign the handlers
	 * to another object.
	 * @param object $handler Object containing the handler method
	 * @param string $function Name of the handler method
	 * @return static
	 */
	function AssignOnAddHeader(&$handler,$function)
	{
		$res = $this->OnAddHeader;
		$this->OnAddHeader = array($handler,$function);
		return $this;
	}

	/**
	 * Allows to assign your own handler to the AddRow function
	 *
	 * Sometimes you do not want to inherit from this, but create a table and assign the handlers
	 * to another object.
	 * @param object $handler Object containing the handler method
	 * @param string $function Name of the handler method
	 * @return static
	 */
	function AssignOnAddRow(&$handler,$function)
	{
		$res = $this->OnAddRow;
		$this->OnAddRow = array($handler,$function);
		return $this;
	}

	/**
	 * Allows to assign your own handler to the AddFooter function
	 *
	 * Sometimes you do not want to inherit from this, but create a table and assign the handlers
	 * to another object.
	 * @param object $handler Object containing the handler method
	 * @param string $function Name of the handler method
	 * @return static
	 */
	function AssignOnAddFooter(&$handler,$function)
	{
		$res = $this->OnAddFooter;
		$this->OnAddFooter = array($handler,$function);
		return $this;
	}

	protected function GetColumns(){return array("*");}
	protected function GetJoin(){return "";}
	protected function GetWhere(){return "";}
	protected function GetGroupBy(){return "";}
	protected function GetHaving(){return "";}
	protected function GetOrderBy(){return "";}
	protected function GetLimit(){return "";}

	/**
	 * Default AddRow method
	 *
	 * This will be called for each row to add (from the execution routines).
	 * If you override this in derivered classes you can easily react on that.
	 * Uses <Table::NewRow>() internally
	 * @param array $data Row as assaciative array
	 * @return void
	 */
	function AddRow(&$data) { $this->NewRow($data); }

	/**
	 * Default AddHeader method
	 *
	 * Creates a table header with the given keys as text.
	 * Uses <Table::Header>() internally
	 * @param array $keys Array of columns this <DatabaseTable> contains
	 * @return void
	 */
	function AddHeader($keys)
	{
		$head = array_combine($keys,$keys);
		$this->Header()->NewRow($head);
	}

	/**
	 * Default AddFooter method
	 *
	 * Creates a table footer with the given keys as text.
	 * Uses <Table::Footer>() internally
	 * @param array $keys Array of columns this <DatabaseTable> contains
	 * @return void
	 */
	function AddFooter($keys)
	{
		$foot = array_combine($keys,$keys);
		$this->Footer()->NewRow($foot);
	}

	protected function _preProcessData($row)
	{
		if( ($this->ParsingBehaviour & self::PB_STRIPHTML) > 0 )
			foreach( $row as $k=>$v )
				$row[$k] = strip_tags($v);
		if( ($this->ParsingBehaviour & self::PB_HTMLSPECIALCHARS) > 0 )
			foreach( $row as $k=>$v )
				$row[$k] = htmlspecialchars("$v");

		if( $this->ParsingBehaviour == self::PB_NOPROCESSING )
		{
//			foreach( $row as $k=>$v )
//			{
//                $v = preg_replace('/<!--(.*)-->/Uis', '', $v); // strip comments
//				$c = 0;
//				if( preg_match_all('/<([^\s\/>]+)>/', $v, $tags, PREG_SET_ORDER) )
//				{
//					foreach( $tags as $t )
//					{
//						if( !preg_match_all('/<\/'.$t[1].'>/', $v, $ctags, PREG_SET_ORDER) )
//							continue;
//						$c++;
//					}
//				}
//
//				$c1 = count(explode('"',$v));
//				$c2 = count(explode("'",$v));
//				$c3 = count(explode(">",$v));
//				$c4 = count(explode("<",$v));
//				if( count($tags)!=$c || ($c1 & 1)==0 || ($c2 & 1)==0 || ($c3 & 1)==0 || ($c4 & 1)==0 )
//                {
//					$row[$k] = htmlspecialchars("$v");
//                    log_debug("spec because $c1 $c2 $c3 $c4",$v);
//                }
//			}
		}
		return $row;
	}

	/**
	 * @override Calls <DatabaseTable::GetData>() and loops thru the <ResultSet> creating the table content before calling <OVERRIDE::DatabaseTable::PreRender>
	 */
	function PreRender($args = [])
	{
        // stop rebuilding the table of row-action was clicked:
        // - performance
        // - row-ids would change and trigger error on subsequent clicked actions
        if( current_event() == 'onactionclicked' && current_controller(false) instanceof Table )
            return parent::PreRender($args);

		$this->GetData();

        if( !$this->ResultSet || $this->ResultSet->Count()==0 )
		{
            $this->addClass('empty');
			if( !$this->noDataAsRow )
            {
                $this->content($this->contentNoData);
                return $this->contentNoData;
            }

			if( !$this->header || $this->header->length()==0 )
				if( $this->OnAddHeader )
					$this->OnAddHeader[0]->{$this->OnAddHeader[1]}($this, []);
				else
					$this->AddHeader([]);

			if( !$this->footer )
				if( $this->OnAddFooter )
					$this->OnAddFooter[0]->{$this->OnAddFooter[1]}($this, []);

			$td = $this->SetColFormat(0,"")->NewCell($this->contentNoData);
			$td->colspan = $this->header->GetMaxCellCount();
			$this->HidePager = true;
		}
        else
        {
            $this->_rowModels = [];
            foreach( $this->ResultSet as $raw_row )
            {
				$row = $this->_preProcessData($raw_row);

                if( !$this->header || $this->header->length()==0 )
                    if( $this->OnAddHeader )
						$this->OnAddHeader[0]->{$this->OnAddHeader[1]}($this, array_keys($row));
                    else
                        $this->AddHeader(array_keys($row));

                $cnt = $this->current_row_group?$this->current_row_group->length():0;
                if( $this->OnAddRow )
                    $this->OnAddRow[0]->{$this->OnAddRow[1]}($this, $row, $raw_row);
                else
                    $this->AddRow($row);

                if( $cnt < ($this->current_row_group?$this->current_row_group->length():0) )
                    $this->AddDataToRow($raw_row);
            }
            if( !$this->footer )
                if( $this->OnAddFooter )
                    $this->OnAddFooter[0]->{$this->OnAddFooter[1]}($this, array_keys($row));
            if($this->alignments)
                $this->SetAlignment($this->alignments);
			if( $this->ItemsPerPage )
                if( !$this->_parent || !($this->par() instanceof \ScavixWDF\Controls\Listing\WdfListing) ) // do not touch HidePager if this is part of a listing
	    			$this->HidePager = false;
        }
		parent::PreRender($args);
	}

	const EXPORT_FORMAT_XLS  = 'xls';
	const EXPORT_FORMAT_XLSX = 'xlsx';
	const EXPORT_FORMAT_CSV  = 'csv';

	static $export_def = array
	(
		'xls'  => array( 'fn'=>'export_{date}.xls',  'mime'=>'application/vnd.ms-excel' ),
		'xlsx' => array( 'fn'=>'export_{date}.xlsx', 'mime'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ),
		'csv'  => array( 'fn'=>'export_{date}.csv',  'mime'=>'text/csv' ),
	);

	/**
	 * @internal Currently untested, so marked <b>internal</b>
	 * @attribute[RequestParam('format','string')]
	 */
	function Export($format, $rowcallback = null)
	{
		switch( $format )
		{
			case self::EXPORT_FORMAT_XLS:
			case self::EXPORT_FORMAT_XLSX:
				$this->_exportExcel($format,$rowcallback);
				break;
			case self::EXPORT_FORMAT_CSV:
				$this->_exportCsv($rowcallback);
				break;
		}
	}

	private function _export_get_header()
	{
		$res = [];
		if( $this->header )
		{
			foreach( $this->header->Rows() as $row )
			{
				$line = [];
				foreach( $row->Cells() as $cell )
				{
					$cc = trim(strip_tags($cell->GetContent()));
    				$cc = rtrim(getString($cc), '?');       // remove trailing ? in case string doesn't exist
					$line[] = $cc;
				}
			}
			$res[] = $line;
		}
		return $res;
	}

	private function _export_get_data(CultureInfo $ci=null, $rowcallback = null)
	{
		$copy = clone $this;
		$copy->ItemsPerPage = false;
		if( $ci )
			$copy->Culture = $ci;
		$copy->GetData();

		$res = [];
		$copy->ResultSet->FetchMode = PDO::FETCH_ASSOC;
        $cols = [];
        if(!$this->Columns && $this->Sql)
            $this->Columns = array_keys($copy->ResultSet->fetchRow()?:[]);
        foreach( $this->Columns as $c )
            $cols[] = trim($c,"`");
		foreach( $copy->ResultSet as $row )
		{
			$row = $copy->_preProcessData($row);
            if ($rowcallback != null)
            {
                $row = $rowcallback($row);
                if($row === false)
                    continue;
            }
            $r = [];
            foreach( $cols as $k )
                if(isset($row[$k]))
                    $r[$k] = $row[$k];
                else
                    $r[$k] = null;

            if( !isset($format_buffer) )
			{
				$format_buffer = [];
				foreach( $cols as $i=>$k )
				{
                    if( isset($this->ColFormats[$i]) )
                        $format_buffer[$k] = $this->ColFormats[$i];
				}
			}
			foreach( $format_buffer as $k=>$cellformat )
				$r[$k] = $cellformat->FormatContent($r[$k],$copy->Culture);
			$res[] = $r;
		}
		return $res;
	}

	/**
	 * @suppress PHP0413,PHP0409
	 */
	protected function _exportExcel($format=self::EXPORT_FORMAT_XLSX, $rowcallback = null)
	{
        //log_debug(__METHOD__,$format);
        if( !class_exists("\\PhpOffice\\PhpSpreadsheet\\Spreadsheet") )
            \ScavixWDF\WdfException::Raise("Missing PhpSpreadsheet. Please install using composer (composer require phpoffice/phpspreadsheet).");

		$xls = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $xls->getActiveSheet();
		$row = 1;
		$max_cell = 0;
		$ci = ExcelCulture::FromCode(isset($this->Culture) && $this->Culture ? $this->Culture->Code : 'en-US');
		$head_rows = $this->_export_get_header();
		$first_data_row = count($head_rows)+1;

        if( !\PhpOffice\PhpSpreadsheet\Settings::setLocale($ci->Code) )
        if( !\PhpOffice\PhpSpreadsheet\Settings::setLocale($ci->LanguageCode) )
            log_debug("Invalid Excel locale. Tried {$ci->Code} and {$ci->LanguageCode}");
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( new \PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder() );

		foreach( array_merge($head_rows,$this->_export_get_data($ci,$rowcallback)) as $data_row )
		{
			foreach( array_values($data_row) as $i=>$val )
			{
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
                $sheet->setCellValue("$col$row",$val);
				if( $i>$max_cell ) $max_cell = $i;
			}
			$row++;
		}

		for($i=0;$i<=$max_cell; $i++)
		{
			$sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
			if( isset($this->ColFormats[$i]) )
			{
				$ef = $ci->GetExcelFormat($this->ColFormats[$i]);
				$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
				// log_debug($i, $col, $this->ColFormats[$i], $ef);
				$sheet->getStyle("$col$first_data_row:$col$row")
					->getNumberFormat()
					->setFormatCode($ef);
			}
		}
		if(count($head_rows))
        	$sheet->freezePane('A2');
        $sheet->setSelectedCell('A1');

        if( isset(self::$export_def[$format]['metadata']) && is_array(self::$export_def[$format]['metadata']) )
        {
            foreach( self::$export_def[$format]['metadata'] as $name=>$value )
            {
                $m = "set{$name}";
                if( method_exists($xls, $m) )
                    $xls->$m($value);
            }
        }

		if( $format == self::EXPORT_FORMAT_XLS )
			$xlswriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($xls);
		else
			$xlswriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($xls);

		$filename = str_replace("{date}",date("Y-m-d_H-i-s"),self::$export_def[$format]['fn']);
		$mime = self::$export_def[$format]['mime'];

		header("Content-Type: $mime");
		header("Content-Disposition: attachment; filename=\"".$filename."\";");
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Cache-Control: private",false);
		$xlswriter->save('php://output');
		die('');
	}

	protected function _exportCsv($rowcallback = null)
	{
		$esc = '"';
		$sep = ',';
		$newline = "\n";
		$csv = [];
		foreach( array_merge($this->_export_get_header(),$this->_export_get_data(null,$rowcallback)) as $row )
		{
			$csv_line = [];
			foreach( $row as $val )
			{
				if( strpos("$val", $sep) !== false )
					$csv_line[] = "$esc$val$esc";
				else
					$csv_line[] = $val;
			}
			$csv[] = implode($sep,$csv_line);
		}

		$csv = implode($newline,$csv);
		$filename = str_replace("{date}",date("Y-m-d_H-i-s"),self::$export_def[self::EXPORT_FORMAT_CSV]['fn']);
		$mime = self::$export_def[self::EXPORT_FORMAT_CSV]['mime'];

		header("Content-Type: $mime");
		header("Content-Disposition: attachment; filename=\"".$filename."\";");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".strlen($csv));
		header('Expires: 0');
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Cache-Control: private",false);
		die($csv);
	}

	protected function RenderPager()
	{
//        if( $this->ItemsPerPage && !$this->HidePager )
//            $this->TotalItems = $this->ResultSet?$this->ResultSet->GetpagingInfo('total_rows'):0;
		return parent::RenderPager();
	}

    public $logIfSlow = false;

    /**
     * Write a log information if querying was slow.
     *
     * @param int $min_ms Minimum milliseconds that must be reached to really write info to log
     * @return static
     */
    function LogIfSlow($min_ms)
    {
        $this->logIfSlow = $min_ms;
        return $this;
    }
}
