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
 * Represents a Chartt.js chart
 * 
 * @attribute[Resource('chart.min.js')]
 * @attribute[Resource('canvas2svg.js')]
 * @attribute[Resource('luxon.js')]
 * @attribute[Resource('chartjs-adapter-luxon.js')]
 * @attribute[Resource('chartjs-plugin-trendline.min.js')]
 * @attribute[Resource('chartjs-plugin-stacked100.js')]
 */
class ChartJS extends Control
{
    protected $chart_title, $canvas;
    protected $series = [];
    protected $config = [];
    protected $detectedCategories = [];
    protected $detectedDateseries = false;
    protected $colorRange = false;
    protected $xMin = false, $xMax = false;
    
    protected static $currentInstance;
    
    public static $NAMED_COLORS = [];
    public static $COLORS = ['red','green','blue','yellow','brown'];
    protected $currentColor = 0;
    
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
        return ['x'=>"[jscode]new Date('".$dt->format("c")."')", 'y'=>$y, 'xval'=>$xval];
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
    public static function Line($title='',$height=false) { return new ChartJS($title,'line',$height); }
    /**
     * @shortcut Create a Bar chart
     */
    public static function Bar($title='',$height=false) { return new ChartJS($title,'bar',$height); }
    /**
     * @shortcut Create a Pie chart
     */
    public static function Pie($title='',$height=false) { return ChartJS::Make($title,'pie',$height)->scaleX('*','display',false); }
    
    /**
     * @shortcut Create a Stacked-Bar chart
     */
    public static function StackedBar($title='',$height=false) { return ChartJS::Make($title,'bar',$height)->setStacked(); }
    
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
    function PreRender($args = [])
    {
        if( $this->_skipRendering )
            return;
        
        if( count($this->detectedCategories)>0 && $this->xLabels()===null )
            $this->xLabels($this->detectedCategories);
        
        $this->scaleX('*',"ticks.maxRotation",0)->scaleX('*','offset',true);
        if( $this->detectedDateseries )
            $this->setTimeAxesX('day');
//            $this->setTimeAxesX(($this->xMin !== false && $this->xMin == $this->xMax)?'day':false);
        
        if( $this->xMin !== false )
            $this->scaleX('*',"ticks.min",$this->xMin);
        if( $this->xMax !== false )
            $this->scaleX('*',"ticks.max",$this->xMax);
        
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
        $this->script("wdf.chartjs.init('{$this->canvas->id}',".system_to_json($this->config).");");
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
            throw new \Exception("Cannot get property '$name' of all datasets");
        
        foreach( $this->series as $i=>$s )
        {
            if( $all || $s['name'] == $index || $index == $i )
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
     * @param string $axes Axes name
     * @param int $index Axes index
     * @param string $name Config name
     * @param mixed $value Optional value
     * @return mixed Returns $this is value is given, else the data requested
     * @throws Exception
     */
    protected function scales($axes,$index,$name,$value=null)
    {
        if( $axes!="xAxes" && $axes!="yAxes" )
            throw new \Exception("Invalid axes '$axes'");
        
        $all = preg_replace('/[*all-]/','',"$index")=="";
        if( $all && $value === null )
            throw new \Exception("Cannot get property '$name' of all scales");
        
        if( $all )
        {
            $ax = $this->opt("scales.$axes")?:[0];
            foreach( array_keys($ax) as $index )
                $this->scales($axes, $index, $name, $value);
            return $this;
        }
        return $this->opt("scales.$axes.$index.$name",$value);
    }
    
    /**
     * @shortcut <ChartJS::scales>
     */
    function scaleX($index,$name,$value=null)
    {
        return $this->scales('xAxes',$index,$name,$value);
    }

    /**
     * @shortcut <ChartJS::scales>
     */
    function scaleY($index,$name,$value=null)
    {
        return $this->scales('yAxes',$index,$name,$value);
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
        return $this->scaleX('*','stacked',true)->scaleY('*','stacked',true);
    }
    
    /**
     * Sets the xAxes to be a time-scale.
     * 
     * @param string $unit OPtional unit specifier
     * @return $this
     */
    function setTimeAxesX($unit=false)
    {
        $this->scaleX('*','type','time')->scaleX('*','distribution','linear');
        if( $unit )
            $this->scaleX('*',"time.unit",$unit);
        if($unit == 'hour')
            $this->scaleX('*',"time.stepSize",2);
        return $this;
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
                    $d[] = ChartJS::$pointType($r[$x_value_row],floatval($v)); 
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
                    $d[] = ChartJS::$pointType($r[$x_value_row],floatval($v),$r); 
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
        foreach( $this->series as &$series )
            $series['data'] = $seriesCallback($series['name']);
		return $this->conf('data.datasets',$this->series);
	}
    
    /**
     * @shortcut Create a multi-series time chart
     */
    public static function MultiSeriesTime($data, $dataset_name='series', $x_name='x', $y_name='y')
	{
        $chart = new ChartJS();
        $series = array_unique(array_map(function($row)use($dataset_name){ return $row[$dataset_name]; },$data));
        if( count($series)<1 )
        {
            log_error("No data series found");
            return $chart;
        }
        $chart->setSeries($series);
        
        $chart->scaleX('*','type','time');
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
}
