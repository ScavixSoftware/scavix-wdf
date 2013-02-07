<?php
/**
 * PamConsult Web Development Framework
 *
 * Copyright (coffee) 2007-2012 PamConsult GmbH
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
 
/**
 * @attribute[Resource('FusionCharts.js')]
 */
class Chart extends Template
{
	var $Width;
	var $Height;
	var $Align = "left";
	protected $Plots = array();
	protected $Trendlines = array();
	protected $LabelFormatFunction = false;
	var $Title;
	var $Type;
	var $BgColor = "ffffff";
	var $NumberSuffix = "%E2%82%AC";
	var $ShowLegend = "1";
	var $ShowValues = "0";
	var $ShowPercentValues = "0";
	var $RotateValues='0';
	var $PlaceValuesInside='0';
	var $SlantLabels = "0";
	var $LabelDisplay = "";
	var $LegendPosition='RIGHT';
	var $EnableSmartLabels='0';
	var $ShowZeroPies = "1";
	var $ShowLabels = "1";
	var $ShowAsPercent = false;
	var $ChartLeftMargin = "";
	var $ChartRightMargin = "";
	var $ChartTopMargin = "";
	var $ChartBottomMargin = "";
	var $ShowSumInLegend = false;
	var $yAxisMaxValue = null;

	//private $arPlotColors = array("e59001", "658966", "B8D1BB", "E02E31", "FFFFFF", "EAA42F", "000000");
	protected $arPlotColors = array("FFC4FF", "FF75FF", "84C418", "FFC488", "FFFFFF", "EAA42F", "000000", "e59001", "658966", "B8D1BB", "E02E31");

	private $_performLazyLoad = false;
	private $_dataFunction = false;
	private $_contentFromCache = false;
	protected $Cache_TimeToLife = 3600;
	public $_ignoreCache = false;
	protected $_cache_hash_fields = array();

	function __initialize($type = "MSColumnLine3D", $title = "", $width = 1000, $height = 450)
	{
		if($type === null)
			$type = "MSColumnLine3D";

		parent::__initialize();

		$this->Width = $width;
		$this->Height = $height;
		$this->Title = $title;
		$this->Type = $type;

		global $CONFIG;
		$swfurl = $CONFIG['system']['system_uri']."modules/charting/charts/".$type.".swf";
		$swfurl .= "?PBarLoadingText=".urlencode(getString(tds('TXT_CHART_LOADING','loading')));
		$swfurl .= "&ChartNoDataText=".urlencode(getString(tds('TXT_NO_DATA_FOUND','no data found')));
		$swfurl .= "&InvalidXMLText=".urlencode(getString(tds('TXT_CHART_INVALID_XML','invalid XML')));

		$this->set("chartswffile", $swfurl);
		$this->set("chartid", $this->_storage_id);
		$this->set("width", $this->Width);
		$this->set("height", $this->Height);
		//$this->set("chartxml", "");

		$this->set("performLazyLoad",false);
		$this->set("debug","0");

		$this->LabelDisplay = "Rotate";
		$this->SlantLabels = "1";

		register_hook(HOOK_POST_EXECUTE,$this,'LoadFromCache');
		register_hook(HOOK_POST_EXECUTE,$this,'PreRender');

		$this->_cache_hash_fields[] = "Width";
		$this->_cache_hash_fields[] = "Height";
		$this->_cache_hash_fields[] = "Align";
		$this->_cache_hash_fields[] = "Title";
		$this->_cache_hash_fields[] = "Type";
		$this->_cache_hash_fields[] = "BgColor";
		$this->_cache_hash_fields[] = "NumberSuffix";
		$this->_cache_hash_fields[] = "ShowLegend";
		$this->_cache_hash_fields[] = "ShowValues";
		$this->_cache_hash_fields[] = "ShowPercentValues";
		$this->_cache_hash_fields[] = "RotateValues";
		$this->_cache_hash_fields[] = "PlaceValuesInside";
		$this->_cache_hash_fields[] = "SlantLabels";
		$this->_cache_hash_fields[] = "LabelDisplay";
		$this->_cache_hash_fields[] = "LegendPosition";
		$this->_cache_hash_fields[] = "EnableSmartLabels";
		$this->_cache_hash_fields[] = "ShowZeroPies";
		$this->_cache_hash_fields[] = "ShowLabels";
		$this->_cache_hash_fields[] = "ShowAsPercent";

		$this->_cache_hash_fields[] = "TimeFrame::FirstDate";
		$this->_cache_hash_fields[] = "TimeFrame::LastDate";
		$this->_cache_hash_fields[] = "BRANDING";

//		if( $type == "Pie2D" || $type == "Pie3D" )
//		{
//			$this->ChartLeftMargin = "15";
//			$this->ChartRightMargin = "15";
//			$this->set("debug","1");
//		}
	}

	function CreateCacheHash()
	{
		$hash  = get_class($this);
		foreach( $this->_cache_hash_fields as $chf )
		{
			switch( $chf )
			{
				case 'TimeFrame::FirstDate':
					$hash .= TimeFrame::FirstDate('Ymd');
					break;
				case 'TimeFrame::LastDate':
					$hash .= TimeFrame::FirstDate('Ymd');
					break;
				case 'BRANDING':
					$hash .= isset($GLOBALS['BRANDING'])&&$GLOBALS['BRANDING']?$GLOBALS['BRANDING']->id:"";
					break;
				default:
					if( is_bool($this->$chf) )
						$hash .= ($this->$chf)?"1":"0";
					else
						$hash .= $this->$chf;
					break;
			}
		}

		$res = md5($hash);
		//log_debug(get_class($this)."->HASH: $res ]-> ".$this->ShowAsPercent);

		return $res;
	}

	function LoadFromCache()
	{
		if( $this->_ignoreCache )
			return;

		$this->_contentFromCache = charting_loadfromcache($this);
		if( $this->_contentFromCache !== false )
		{
			$this->_dataFunction = false;
			$this->_performLazyLoad = false;
			$this->set("performLazyLoad",false);
			//log_debug("deleting object $this");
			delete_object($this);
		}
	}

	// @TODO: really required? this doesn�t work with lazyload
	function SetDataFunction($functionName,$lazy=false)
	{
		$this->_dataFunction = $functionName;
		$this->_performLazyLoad = $lazy;
		$this->set("performLazyLoad",$this->_performLazyLoad);
	}

	function CleanupXML($xml)
	{
		$xml = str_replace("			", " ", $xml);
		$xml = str_replace("\r\n", "", $xml);
		$xml = str_replace("\n", "", $xml);
		return $xml;
	}

	function PreRender()
	{
		if( $this->_contentFromCache === false && $this->_dataFunction !== false && !$this->_performLazyLoad )
		{
			//log_debug("calling datafunction for $this");
			$this->Plots = array();
			$this->Trendlines = array();
			$this->{$this->_dataFunction}();
		}

		if( $this->_contentFromCache !== false )
		{
			$this->set("chartxml", $this->CleanupXML($this->_contentFromCache));
		}
		elseif( !$this->_performLazyLoad )
		{
			$this->set("chartxml", $this->CleanupXML($this->RenderXML()));
		}
		else
		{
			store_object($this);
		}
		
		if( $this->_performLazyLoad && $this->_contentFromCache === false)
		{
			$id  = $this->_storage_id;
			$fn  = "$id.setDataXML(d);";
			$fn .= "$id.setTransparent(true);";
			$fn .= "$id.render('dv".$id."');";
			$code = "$.get('?',{load:'$id',event:'LazyLoad'},function(d){ $fn });";
			$this->script("$code");
		}
	}

	function LazyLoad()
	{
		// @todo: $this->_dataFunction is not used for lazyload because of problems with this mode
		$this->GenerateData();
//		if( $this->_dataFunction )
//		{
//			$this->Plots = array();
//			$this->Trendlines = array();
//			$this->{$this->_dataFunction}();
//		}

//		$res = __translate($this->RenderXML());
		return $res;
	}

	function &AddPlot($title = "", $type = "")
	{
		if($type == "")
			$type = $this->Type;
		$plot = new Plot($type, $title);
		$this->Plots[] = $plot;
		return $plot;
	}

	function &AddTrendLine($displayName,$startValue,$endValue=false)
	{
		$tl = new Trendline($displayName,$startValue,$endValue);
		$this->Trendlines[] = $tl;
		return $tl;
	}

	function IsMultiPlot()
	{
		return substr($this->Type,0,2) == 'MS' ||
			count($this->Plots) > 1 ||
			$this->Type == "StackedBar2D" ||
			$this->Type == "StackedColumn2D" ||
			$this->Type == "StackedBar3D" ||
			$this->Type == "StackedColumn3D";
	}

	function IsAllowedObject($type)
	{
		if( $this->Type == 'StackedBar2D' && $type == "ANCHORS" )
			return false;
		if( $this->Type == 'StackedColumn2D' && $type == "ANCHORS" )
			return false;
		if( $this->Type == 'Column2D' && $type == "ANCHORS" )
			return false;
		if( $this->Type == 'Pie2D' && $type == "ANCHORS" )
			return false;
		if( $this->Type == 'Pie2D' && $type == "DATAVALUES" )
			return false;
		if( $this->Type == 'Pie3D' && $type == "ANCHORS" )
			return false;
		if( $this->Type == 'Pie3D' && $type == "DATAVALUES" )
			return false;
		return true;
	}

	function FormatLabel($label)
	{
		if( $this->LabelFormatFunction )
			return call_user_func($this->LabelFormatFunction,$label);
			//return $this->LabelFormatFunction($label);
		return $label;
	}

	protected function RenderXML()
	{
		// fill missing data in plots with zero values
		$pcnt = count($this->Plots);
		for($i=0; $i<$pcnt; $i++)
		{
			$pcdcnt = count($this->Plots[$i]->Data);
			for($k=0; $k<$pcdcnt; $k++)
			{
				for($j=0; $j<$pcnt; $j++)
				{
					if( $i==$j ) continue;

					if( !$this->Plots[$j]->hasValue($this->Plots[$i]->Data[$k]->Label)
						&& (
							!isset($this->Plots[$j]->Data[$k]) ||
							$this->Plots[$j]->Data[$k]->Label != $this->Plots[$i]->Data[$k]->Label
						) )
					{
						//log_debug($this->Plots[$j]->Title.": Inserting zero value ".$this->Plots[$i]->Data[$k]->Label);
//						if( strpos($this->Type,"Bar") === false && strpos($this->Type,"Column") === false )
//							$this->Plots[$j]->Insert(0,$this->Plots[$i]->Data[$k]->Label,0);
//						else
							$this->Plots[$j]->Insert("",$this->Plots[$i]->Data[$k]->Label,0);
//						$v = $this->Plots[$j]->Insert(0,$k);
//						$v->Label = $this->Plots[$i]->Data[$k]->Label;
					}
				}
			}
		}

		// recalc values if chart should fill to 100%
		if( $this->ShowAsPercent && count($this->Plots) > 0 )
		{
			//log_debug(get_class($this)." showing as percent");
			foreach( $this->Plots[0]->GetLabels() as $lab )
			{
				$sum = 0;
//				log_debug("Processing '$lab'...");
				foreach( $this->Plots as &$plot )
				{
					$tmp_val = $plot->getValue($lab);
					if( $tmp_val != "" )
						$sum += $plot->getValue($lab);
				}
				foreach( $this->Plots as &$plot )
				{
					$plot->PercentValue($lab,$sum);
				}
			}
			$this->NumberSuffix = "%25";
		}

		$ret = "<chart
		  palette='2'
			showLegend='".$this->ShowLegend."'
			showValues='".$this->ShowValues."'
			showPercentValues='".$this->ShowPercentValues."'
			numberSuffix='".$this->NumberSuffix."'
			rotateValues='".$this->RotateValues."'
			placeValuesInside='".$this->PlaceValuesInside."'
			decimalSeparator=','
			thousandSeparator='.'
			decimals='0'
			formatNumberScale='0' yAxisValuesPadding='10'
			bgColor='".$this->BgColor."'
			borderThickness='0'
			borderColor='ffffff'
			canvasBorderThickness='1'
			canvasBorderColor='a0a0a0'
			exportEnabled='1' exportHandler='system/modules/charting/FCExporter.php' exportAtClient='0' exportAction='download' exportFileName='Chart'
			connectNullData='0' lineDashGap='6'
			utCnvBaseFont='Verdana,Arial' outCnvBaseFontSize='11'
			labelDisplay='".$this->LabelDisplay."'
			slantLabels='".$this->SlantLabels."'
			legendPosition='".$this->LegendPosition."'
			enableSmartLabels='".$this->EnableSmartLabels."'
			showZeroPies='".$this->ShowZeroPies."'
			showLabels='".$this->ShowLabels."'
			showCanvasBg='0'
			canvasBgColor='ffffff'";
		if(!is_null($this->yAxisMaxValue))
			$ret .= "\ryAxisMaxValue='".$this->yAxisMaxValue."' ";
		if($this->ChartLeftMargin != "")
			$ret .= "\rchartLeftMargin='".$this->ChartLeftMargin."' ";
		if($this->ChartRightMargin != "")
			$ret .= "\rchartRightMargin='".$this->ChartRightMargin."' ";
		if($this->ChartTopMargin != "")
			$ret .= "\rchartTopMargin='".$this->ChartTopMargin."' ";
		if($this->ChartBottomMargin != "")
			$ret .= "\rchartBottomMargin='".$this->ChartBottomMargin."' ";


		$plotxml = "";
		$this->xAxisLabels = array();
		$plotindex = 0;
		foreach($this->Plots as &$plot)
		{
			if( $plot->Color == "" && isset($this->arPlotColors[$plotindex++]) )
				$plot->Color = $this->arPlotColors[$plotindex];
            if($this->ShowSumInLegend && !$this->ShowAsPercent)
                $plot->ShowSumInLegend = true;
			$plotxml .= $plot->Render($this);
		}

		if( count($this->xAxisLabels) > 20 )
			$ret .= "labelStep='7' ";

		if($this->Title != "")
			$ret .= "caption='".$this->Title."' ";
		if(substr($this->Type, 0, 3) == "Pie")
			$ret .= " pieYScale='60' startingAngle='-180'";
		$ret .= ">";
		$ret .= $plotxml;

		if($this->IsMultiPlot())
		{
			$ret .= "<dataset></dataset>";

		  	$ret .= "<categories>";
			$lblcnt = count($this->xAxisLabels);
		  	for($i = 0; $i < $lblcnt; $i++)
				$ret .= "<category label='".$this->xAxisLabels[$i]."' />";
			$ret .= "</categories>";
		}

		if( count($this->Trendlines) > 0 )
		{
			$trendlines = array();
			foreach( $this->Trendlines as $tl )
			{
				$trendlines[] = $tl->Render();
			}
			$ret .= "<trendLines>".implode("",$trendlines)."</trendLines>";
		}

		$ret .= "
		<styles>
	        <definition>
	            <style name='myToolTipFont' type='font' font='Verdana, Arial' size='12' />
	            <style name='myAxisFont' type='font' font='Verdana, Arial' size='11' />
	            <style name='myBevel' type='bevel' distance='4' />
	        </definition>
	        <application>
	            <apply toObject='ToolTip' styles='myToolTipFont' />
	            <apply toObject='DataLabels' styles='myAxisFont' />";
	    if( $this->IsAllowedObject('DATAVALUES') )
			$ret .= "<apply toObject='DataValues' styles='myAxisFont' />";
	    if( $this->IsAllowedObject('ANCHORS') )
			$ret .= "<apply toObject='ANCHORS' styles='myBevel' />";
	    $ret .= "    </application>
    	</styles>";

		$ret .= "\r\n</chart>";

//		if( TimeFrame::LastDate() < time()-86400 )
//			charting_storetocache($this,$ret,86400 * 365);
//		else
        //disable different caching methods as hotfix for the graphics
        if( !$this->_ignoreCache )
			charting_storetocache($this,$ret,$this->Cache_TimeToLife);

		return $ret;
	}

	public function PrepareLabel($label)
	{
		$label = utf8_decode(str_replace("'", "%26apos;", $label));
		$label = str_replace("&euro;", "%E2%82%AC", $label);   // if already html input
		$label = htmlentities($label);
	 	$label = str_replace("&amp;", "%26", $label);		// FusionCharts v3 can't handle &amp; ...
	 	$label = str_replace("%;", "%25", $label);
	 	return $label;
	}
}

?>