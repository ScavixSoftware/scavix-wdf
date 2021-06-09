<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2017-2019 Scavix Software Ltd. & Co. KG
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
 * @copyright 2017-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls;

use ScavixWDF\Base\Control;
use ScavixWDF\Base\DateTimeEx;

/**
 * Represents a Chart.js chart
 * 
 * @attribute[Resource('chartjs3/chart.min.js')]
 * @attribute[Resource('chartjs3/luxon.js')]
 * @attribute[Resource('chartjs3/chartjs-adapter-luxon.js')]
 * @attribute[Resource('chartjs3/chartjs-plugin-trendline.js')]
 * @attribute[Resource('canvas2svg.js')]
 */
class ChartJS3 extends Control
{
    var $chart_title, $canvas;
    var $series = [];
    var $config = array();
    var $detectedCategories = [];
    var $detectedDateseries = false;
    var $colorRange = false;
    var $xMin = false, $xMax = false;
    var $query = false;
    
    protected static $currentInstance;
    
    public static $NAMED_COLORS = [];
    public static $COLORS = ['red','green','blue','yellow','brown'];
    protected $currentColor = 0;
    protected $xBasedData = false;
    
    public static $CI = false;
    
    protected function setXMinMax($val)
    {
        if( $val instanceof \DateTime )
            $val = $val->getTimestamp()*1000; // in ms for JS
        if( !$this->xMin || $val < $this->xMin ) $this->xMin = $val;
        if( !$this->xMax || $val > $this->xMax ) $this->xMax = $val;
        return $val;
    }
    
    /**
     * @internal Handler for points of type Time
     */
    public static function TimePoint($x,float $y)
    {
        $dt = DateTimeEx::Make($x);
        $xval = self::$currentInstance->setXMinMax($dt);
        //return ['x'=>"[jscode]new Date('".$dt->format("c")."')", 'y'=>$y, 'xval'=>$xval];
        return ['x'=>$dt->format("c"), 'y'=>$y, 'xval'=>$xval];
    }
    
    /**
     * @internal Handler for points of type Date
     */
    public static function DatePoint($x,float $y,$row=false)
    {
        self::$currentInstance->detectedDateseries = true;
        $dt = DateTimeEx::Make($x);
        $xval = self::$currentInstance->setXMinMax($dt);
        $pt = ['x'=>$dt->format("Y-m-d"), 'y'=>$y, 'xval'=>$xval];
        if( $row ) $pt['raw'] = $row;
        return $pt;
    }

    /**
     * @internal Handler for points of type String
     */
    public static function StrPoint(string $x,float $y,$row=false)
    {
        if( !in_array($x,self::$currentInstance->detectedCategories) )
            self::$currentInstance->detectedCategories[] = $x;
        $pt = ['x'=>$x, 'y'=>$y];
        if( $row ) $pt['raw'] = $row;
        return $pt;
    }
    
    /**
     * @shortcut Create a Line chart
     */
    public static function Line($title='',$height=false) { return new ChartJS3($title,'line',$height); }
    /**
     * @shortcut Create a Bar chart
     */
    public static function Bar($title='',$height=false) { return new ChartJS3($title,'bar',$height); }
    /**
     * @shortcut Create a Pie chart
     */
    public static function Pie($title='',$height=false) { return ChartJS3::Make($title,'pie',$height)->scaleX('display',false); }
    
    /**
     * @shortcut Create a Stacked-Bar chart
     */
    public static function StackedBar($title='',$height=false) { return ChartJS3::Make($title,'bar',$height)->setStacked(); }
    
	function __construct($title='',$type='line',$height=false)
	{
        self::$currentInstance = $this;
		parent::__construct('div');

        $this->chart_title = $this->content(Control::Make('div')->append($title))->addClass('caption');
        $this->canvas = Control::Make('canvas');
        $wrap = $this->content($this->canvas->wrap('div'))->addClass('wrap');
        
        $this->setType($type)
            ->opt('responsive',true)
            ->opt('maintainAspectRatio',false);
        if( $height )
            $wrap->css('height',$height);
        
        \ScavixWDF\Base\HtmlPage::AddPolyfills('default,Object.assign,Object.is');
        \ScavixWDF\Base\HtmlPage::AddPolyfills('Number.isNaN,String.prototype.repeat');
        \ScavixWDF\Base\HtmlPage::AddPolyfills('Math.trunc,Math.sign');
	}
    
    /**
     * @override
     */
    function PreRender($args = array())
    {
        if( $this->_skipRendering )
            return;
        
        if( count($this->detectedCategories)>0 && $this->xLabels()===null )
            $this->xLabels($this->detectedCategories);
        
        $this->scaleX("ticks.maxRotation",0)->scaleX('offset',true);
        if( $this->detectedDateseries )
            $this->setTimeAxesX('day');
        
        if( $this->xMin !== false )
            $this->scaleX("ticks.min",$this->xMin);
        if( $this->xMax !== false )
            $this->scaleX("ticks.max",$this->xMax);
        
        if( count($args) > 0 && self::$CI instanceof \ScavixWDF\Localization\CultureInfo )
        {
            $lang = self::$CI->ResolveToLanguage()->Code;
            $script = "try{ luxon.Settings.defaultLocale = '$lang'; } catch(ex){ console.log('lucon error',ex); }";
            if( $args[0] instanceof \ScavixWDF\Base\HtmlPage )
                $args[0]->addDocReady($script);
            else
                $this->script($script);
        }
        
        foreach( $this->series as $i=>$series )
        {
            if( count($series['data']) < 100 )
                continue;
            $this->dataset($i,'type','line');
            $this->dataset($i,'fill',false);
            $this->dataset($i,'pointRadius',0);
            $this->dataset($i,'lineTension',0);
        }
        
        //log_debug(__METHOD__,$this->config);
        $this->script("wdf.chartjs3.init('{$this->canvas->id}',".system_to_json($this->config).");");
        parent::PreRender($args);
        $this->_skipRendering = true;
    }
    
    protected function conf($name,$value=null)
	{
        $parts = explode(".",$name);
        if( $value === null )
        {
            $cfg = $this->config;
            foreach( $parts as $n )
            {
                if( !isset($cfg[$n]) )
                    return null;
                $cfg = $cfg[$n];
            }
            return $cfg;
        }
        $cfg = &$this->config;
        foreach($parts as $n)
            $cfg = &$cfg[$n];
        $cfg = $value;
		return $this;
	}
    
    /**
	 * Sets or gets an option
	 * 
	 * if you specify a $value will set it and retunr `$this`. else will return the option value
	 * @param string $name option name
	 * @param mixed $value option value or null
	 * @return mixed If setting an option returns `$this`, else returns the option value
	 */
	function opt($name,$value=null)
	{
        return $this->conf("options.$name",$value);
	}
    
    /**
     * Get/Set dataset related configuration.
     * 
     * @param int $index Datase index
     * @param string $name Config name
     * @param mixed $value Optional value
     * @return mixed Returns $this is value is given, else the data requested
     * @throws Exception
     */
    function dataset($index,$name,$value=null)
	{
        $all = preg_replace('/[*all-]/','',"$index")=="";
        if( $all && $value === null )
            throw new Exception("Cannot get property '$name' of all datasets");
        
        foreach( $this->series as $i=>$s )
        {
            //log_debug("[$index] series $i",$s);
            if( $all || $s['name'] == "$index" || "$index" == "$i" )
            {
                if( $value === null )
                    return $this->conf("data.datasets.$i.$name");
                $this->conf("data.datasets.$i.$name",$value);
                if( !$all )
                    break;
            }
        }
        return $this;
	}
    
    /**
     * Get/Set scale related configuration.
     * 
     * @param string $axes Scale ID
     * @param int $index Axes index
     * @param string $name Config name
     * @param mixed $value Optional value
     * @return mixed Returns $this is value is given, else the data requested
     * @throws Exception
     */
    protected function scales($scaleId,$name,$value=null)
    {
        if( $scaleId == "*" )
        {
            if( $value === null )
                throw new Exception("Cannot get property '$name' of all scales");
            
            $scales = array_merge(['x'=>[],'y'=>[]],$this->opt("scales")?:[]);
            foreach( $scales as $id=>$sc )
                $this->scales($id, $name, $value);
            return $this;
        }
        if( is_array($value) )
        {
            foreach( $value as $k=>$v )
                $this->scales("$scaleId.$name",$k,$v);
            return $this;
        }
        $this->opt("scales.$scaleId.$name",$value);
        //log_debug("scales.$scaleId",$this->opt("scales.$scaleId"));
        return $this;
    }
    
    /**
     * @shortcut <ChartJS3::scales>
     */
    function scaleX($name,$value=null)
    {
        return $this->scales('x',$name,$value);
    }

    /**
     * @shortcut <ChartJS3::scales>
     */
    function scaleY($name,$value=null)
    {
        return $this->scales('y',$name,$value);
    }
    
    /**
     * Gets/Sets X-Axes labels.
     * 
     * @param array $labels Optional labels as array
     * @return mixed $this is lables is given, else the data requested
     */
    function xLabels($labels=null)
    {
        return $this->conf('data.labels',$labels);
    }
    
    /**
     * Gets/Sets the legend.
     * 
     * @param string $name Name
     * @param mixed $value Optional value
     * @return mixed Returns $this is value is given, else the data requested
     */
    function legend($name,$value=null)
    {
        return $this->opt("legend.$name",$value);
    }
    
    /**
     * Sets the chart type.
     * 
     * @param string $type The type name
     * @return $this
     */
    function setType($type)
    {
        $this->config['type'] = $type;
        return $this;
    }
    
    /**
     * Sets the chart title.
     * 
     * @param string $text Title
     * @return $this
     */
    function setTitle($text)
    {
        $this->chart_title->content($text,true);
        return $this;
    }
    
    /**
     * Sets this chart to be stacked.
     * 
     * @return $this
     */
    function setStacked()
    {
        return $this->scales('*','stacked',true);
    }
    
    /**
     * Sets the xAxes to be a time-scale.
     * 
     * @param string $unit OPtional unit specifier
     * @return $this
     */
    function setTimeAxesX($unit=false)
    {
        $this->scaleX('type','timeseries');
        if( $unit )
            $this->scaleX("time.unit",$unit);
        if($unit == 'hour')
            $this->scaleX("time.stepSize",2);
        return $this;
    }
    
    function setPercentAxesY()
    {
        if( $this->opt("percentScale",true)->percentizeData() )
            $this->conf('data.datasets',$this->series);
        
        return $this->scaleY('min',0)->scaleY('max',100)->scaleY('ticks',[
            'callback'=>"function(value,index,values){ return value+'%'; }",
        ]);
    }
    
    /**
     * Sets a <ColorRange> for the chart.
     * 
     * @param ColorRange $range Range of colors
     * @return $this
     */
    function setColorRange(\ScavixWDF\Base\Color\ColorRange $range)
    {
        $this->colorRange = $range;
        return $this;
    }
    
    protected function getColor($name=false,$label=false,$value=false)
    {
        if( $value && $this->colorRange )
            return "".$this->colorRange->fromValue($value);
        $col = ifavail(self::$NAMED_COLORS,$name,$label);
        return $col?$col:(self::$COLORS[($this->currentColor++)%count(self::$COLORS)]);
    }
    
    /**
     * Sets series names.
     * 
     * @param string $seriesNames Series names
     * @param bool $append If true, series will be appended, else existing will be replaced
     * @return $this
     */
    function setSeries($seriesNames, $append=false)
    {
        if( !$append ) $this->series = [];
        $present = array_map(function($s){ return $s['name']; },$this->series);
        foreach( $seriesNames as $name=>$label )
        {
            if( is_numeric($name) )
                $name = $label;
            if( in_array($name, $present) )
                continue;
            $backgroundColor = $borderColor = $this->getColor($name,$label);
            $this->series[] = compact('name','label','borderColor','backgroundColor');
        }
        return $this;
    }
    
    /**
     * Sets data.
     * 
     * @param iterable $data The actual data
     * @param string $x_value_row Name of the field that represents the series name
     * @param string $pointType Optional classname of the Point handler
     * @return $this
     */
    function setChartData(iterable $data, string $x_value_row, $pointType="StrPoint")
    {
        return $this->fill(function($series)use($data, $x_value_row, $pointType)
        {
            $d = []; 
            foreach( $data as $r ) 
            {
                if( is_callable($pointType) )
                    $d[] = $pointType($r,$series,$x_value_row);
                else
                {
                    $v = isset($r[$series])?$r[$series]:0;
                    $d[] = ChartJS3::$pointType($r[$x_value_row],floatval($v)); 
                }
            }
            //log_debug("SER $series",$d);
            return $d; 
        });
    }
    
    /**
     * Sets series data.
     * 
     * @param iterable $data The actual data
     * @param string $series_row Name of the field with the series name
     * @param string $x_value_row Name of the field with the x-values
     * @param string $y_value_row Name of the field with the y-values
     * @param string $pointType Optional classname of the Point handler
     * @return $this
     */
    function setSeriesData(iterable $data, string $series_row, string $x_value_row, string $y_value_row, $pointType="StrPoint")
    {   
        $series = [];
        foreach( $data as $r )
            $series[] = $r[$series_row];
        
        $this->setSeries(array_filter(array_unique($series)));
        return $this->fill(function($series)use($data, $series_row, $x_value_row, $y_value_row, $pointType)
        {
            $d = []; 
            foreach( $data as $r ) 
            {
                if( ifavail($r,$series_row) != $series )
                    continue;
                
                if( is_callable($pointType) )
                    $d[] = $pointType($r,$series,$series_row,$x_value_row,$y_value_row);
                else
                {
                    $v = isset($r[$y_value_row])?$r[$y_value_row]:0;
                    $d[] = ChartJS3::$pointType($r[$x_value_row],floatval($v),$r); 
                }
            }
//            log_debug("MULTISER $series",$d);
            return $d; 
        });
    }
    
    /**
     * Sets data for a Pie chart.
     * 
     * @param array $name_value_pairs key-value pairs of data
     * @return $this
     */
    function setPieData(array $name_value_pairs)
    {
        $labels = []; $this->series = [['data'=>[],'backgroundColor'=>[],'borderColor'=>[]]];
        foreach( $name_value_pairs as $lab=>$val )
        {
            $labels[] = $lab;
            $col = $this->getColor($lab,false,$val);
            $this->series[0]['backgroundColor'][] = $col;
            $this->series[0]['borderColor'][] = $col;
            $this->series[0]['data'][] = floatval($val);
        }
        return $this->xLabels($labels)->conf('data.datasets',$this->series);
    }

    /**
     * Fill the chart with data using a callback.
     * 
     * @param callable $seriesCallback Callback that will receive the series name and must return an array with data
     * @return $this
     */
    function fill($seriesCallback)
	{
        $harmonize = ($this->conf('type') != 'pie');
        $data = [];
        foreach( $this->series as &$series )
        {
            $series['data'] = $seriesCallback($series['name']);
            if( !$harmonize )
                continue;
            foreach( $series['data'] as $point )
            {
                $data[$point['x']]['data'][$series['name']] = $point['y'];
                $data[$point['x']]['total'] = (isset($data[$point['x']]['total']))
                    ?$data[$point['x']]['total']+$point['y']
                    :$point['y'];
            }
        }
        if( $harmonize )
        {
            $this->xBasedData = $data;
            $this->harmonizeData();
            $this->percentizeData();
        }
		return $this->conf('data.datasets',$this->series);
	}
    
    protected function harmonizeData()
    {
        if( !$this->xBasedData || count($this->xBasedData)==0 )
            return false;
        
        foreach( $this->xBasedData as $x=>&$point )
        {
            foreach( $this->series as &$series )
            {
                if( isset($point['data'][$series['name']]) )
                    continue;
                //$point['data'][$series['name']] = 0;
                $series['data'][] = ['x'=>$x,'y'=>null];
//                log_debug("{$series['name']}: adding missing point $x");
            }
        }
        foreach( $this->series as &$series )
            usort($series['data'], function($a,$b){ return $a['x']<$b['x']?-1:($a['x']>$b['x']?1:0); });

        return true;
    }
    
    protected function percentizeData()
    {
        if( !$this->opt('percentScale') || !$this->xBasedData || count($this->xBasedData)==0 )
            return false;
        
        foreach( $this->xBasedData as $x=>$point )
        {
            foreach( $point['data'] as $sn=>$y )
            foreach( $this->series as &$series )
            {
                if( $series['name'] != $sn )
                    continue;

                foreach( $series['data'] as &$pt )
                {
                    if( $pt['x'] != $x )
                        continue;
                    
                    $pt['yval'] = $pt['y'];
                    $pt['y'] = ($point['total']>0)
                        ?$pt['yval'] / $point['total'] * 100
                        :0;
//                    log_debug("$sn($x) = {$pt['yval']} => {$pt['y']}% of {$point['total']}");
                }
//                log_debug($series['name'],$series['data']);
                break;
            }
        }
        return true;
    }


    /**
     * @shortcut Create a multi-series time chart
     */
    public static function MultiSeriesTime($data, $dataset_name='series', $x_name='x', $y_name='y')
	{
        // todo: use this logik in every place where data is set. then try to create some kind of
        //       detection for automatic ajax-delay-loading
        if( $data instanceof \ScavixWDF\Model\Model || $data instanceof ScavixWDF\Model\ResultSet )
        {
            $this->query = $data;
            $data = $data->results();
        }
        
        $chart = new ChartJS3();
        $series = array_unique(array_map(function($row)use($dataset_name){ return $row[$dataset_name]; },$data));
        if( count($series)<1 )
        {
            log_error("No data series found");
            return $chart;
        }
        $chart->setSeries($series);
        
        $chart->scaleX('type','time');
        return $chart->fill(function($name)use($data,$dataset_name,$x_name,$y_name)
        {
            $res = [];
            foreach( $data as $row )
            {
                if( $row[$dataset_name] != $name )
                    continue;
                if( !isset($row[$x_name]) && !isset($row[$y_name]) )
                {
                    log_error("No data $x_name/$y_name found");
                    break;
                }
                $val = ifavail($row,$y_name)?floatval($row[$y_name]):false;
                if( $val === false )
                    continue;
                $res[] = self::TimePoint(DateTimeEx::Make(ifavail($row,$x_name)),$val);
            }
            return $res;
        });
	}
    
    /**
     * Iterates series.
     * 
     * @param callable $callback Callback that received each searies
     * @return $this
     */
    public function eachSeries($callback)
    {
        foreach( $this->series as &$series )
        {
            $callback($series);
        }
        return $this->conf('data.datasets',$this->series);
    }
    
    /**
     * Prepare the data to be ajax usable.
     * 
     * @return array The data
     */
    public function getAjaxData()
    {
        $this->PreRender();
        return $this->conf('data');
    }
    
    public function setDelayed($url, $refresh_interval = -1)
    {
        // todo: see MultiSeriesTime for comment on how to delay better
        return $this->opt('refresh',['interval'=>$refresh_interval,'url'=>$url] );
    }
}
