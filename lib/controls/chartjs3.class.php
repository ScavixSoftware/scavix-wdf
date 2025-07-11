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
 * @attribute[Resource('chartjs3/chartjs-plugin-datalabels.js')]
 * @attribute[Resource('chartjs3/canvas2svg.js')]
 */
class ChartJS3 extends Control
{
    public $chart_title, $canvas;
    public $series = [];
    public $config = [];
    public $detectedCategories = [];
    public $detectedDateseries = false;
    public $colorRange = false;
    public $xMin = false, $xMax = false;
    public $query = false;
    public $colors, $named_colors;
    public $series_order;

    protected static $currentInstance;

    public static $NAMED_COLORS = [];
    public static $COLORS = ['red','green','blue','yellow','brown'];
    protected $currentColor = 0;
    protected $xBasedData;
    protected $missingPointCallback = false;
    protected $orderByValue = false;
    protected int $topXOnly = 0;
    protected $topXOthersName = false;
    protected $seriesRankByValue = [];

    public static $CI;

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
        $dt = ($x instanceof DateTimeEx) ? $x : DateTimeEx::Make($x);
        $xval = self::$currentInstance->setXMinMax($dt);
        //return ['x'=>"[jscode]new Date('".$dt->format("c")."')", 'y'=>$y, 'xval'=>$xval];
        return ['x'=>$dt->getTimestamp()*1000, 'y'=>$y, 'xval'=>$xval];
    }

    /**
     * @internal Handler for points of type Date
     */
    public static function DatePoint($x,float $y,$row=false)
    {
        self::$currentInstance->detectedDateseries = true;
        $dt = ($x instanceof DateTimeEx) ? $x : DateTimeEx::Make($x);
        $xval = self::$currentInstance->setXMinMax($dt);
        $pt = ['x'=>$dt->format("Y-m-d"), 'y'=>$y, 'xval'=>$xval];
        if( $row ) $pt['raw'] = $row;
        return $pt;
    }

    /**
     * @internal Handler for points of type Week
     */
    public static function WeekPoint($x,float $y,$row=false)
    {
        $dt = DateTimeEx::Make($x);
        list($year,$week) = explode(" ",$dt->format('o W'));
        $xval = DateTimeEx::FirstDayOfWeek("{$year}-01-01 00:00:00")
            ->Offset($week,"week")->getTimestamp();

        $pt = ['x'=>"$year W $week", 'y'=>$y, 'xval'=>$xval];
        if( $row ) $pt['raw'] = $row;
        return $pt;
    }

    /**
     * @internal Handler for points of type String
     */
    public static function StrPoint(string $x,float $y,$row=false)
    {
        if( self::$currentInstance && is_array(self::$currentInstance->detectedCategories) && !in_array($x,self::$currentInstance->detectedCategories) )
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
     * @shortcut Create a Doughnut chart
     */
    public static function Doughnut($title='',$height=false) { return ChartJS3::Make($title,'doughnut',$height)->scaleX('display',false); }

    /**
     * @shortcut Create a Stacked-Bar chart
     */
    public static function StackedBar($title='',$height=false) { return ChartJS3::Make($title,'bar',$height)->setStacked(); }

	function __construct($title='',$type='line',$height=false)
	{
        self::$currentInstance = $this;
		parent::__construct('div');

        $this->colors = []+self::$COLORS;
        $this->named_colors = []+self::$NAMED_COLORS;

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

        $this->scaleX("ticks.maxRotation",0)->scaleX('offset',true)->scaleX('autoSkip',false);
        if( $this->detectedDateseries )
            $this->setTimeAxesX('day');

        if( $this->xMin !== false )
            $this->scaleX("ticks.min",$this->xMin);
        if( $this->xMax !== false )
            $this->scaleX("ticks.max",$this->xMax);

        if( \ScavixWDF\Wdf::Once(__METHOD__) && self::$CI instanceof \ScavixWDF\Localization\CultureInfo )
        {
            $lang = self::$CI->ResolveToLanguage()->Code;
            $script = "try{ luxon.Settings.defaultLocale = '$lang'; } catch(ex){ console.log('luxon error',ex); }";
            $this->script($script);
        }

        if( $this->series_order && count($this->series)>1 )
        {
            $sort = function($a,$b)
            {
                $ia = array_search($a['name'],$this->series_order);
                $ib = array_search($b['name'],$this->series_order);
                if( $ia == $ib ) return 0;
                return $ia<$ib?-1:($ia>$ib?1:0);
            };
            usort($this->series,$sort);
            // $this->legend('reverse',true);
        }

        if ($this->conf('type') != 'pie' && $this->topXOnly > 0 && count($this->series) > ($this->topXOnly+1)) // +1 because combining 1 to 'others' is useless
        {
            $others = array_splice($this->series, $this->topXOnly);
            $new = $others[0];
            $new['name'] = 'generated_others_series';
            $new['label'] = $this->topXOthersName;
            $new['data'] = [];
            foreach( $others as $o )
            {
                if( count($new['data']) == 0 )
                {
                    foreach ($o['data'] as $entry)
                        $new['data'][] = array_merge(sub_array($entry, ['x']), ['y' => 0]);
                }
                log_debug("Other",$o);
                foreach ($o['data'] as $i => $entry)
                    $new['data'][$i]['y'] += $entry['y'];
            }
            $this->series[] = $new;
        }

        foreach( $this->series as $i=>&$series )
        {
            if( $this->series_order && ifavail($series,'isPieData') )
            {
                $sort = function($a,$b)
                {
                    $ia = array_search($a,$this->series_order);
                    $ib = array_search($b,$this->series_order);
                    if( $ia == $ib ) return 0;
                    return $ia<$ib?-1:($ia>$ib?1:0);
                };
                uksort($series['data'],$sort);
                uksort($series['backgroundColor'],$sort);
                uksort($series['borderColor'],$sort);
            }

            if( ifavail($series,'isPieData') )
            {
                unset($series['isPieData']);
                $series['data'] = array_values($series['data']);
                $series['backgroundColor'] = array_values(force_array($series['backgroundColor']));
                $series['borderColor'] = array_values(force_array($series['borderColor']));
            }

            if( count($series['data']) < 100 || ifavail($series,'raw_large_datasets') )
                continue;
            $series['type'] = 'line';
            $series['fill'] = false;
            $series['lineTension'] = 0;

            if( !isset($series['elements']) )
				$series['elements'] = [];
			if( !isset($series['elements']['point']) )
				$series['elements']['point'] = [];
			$series['elements']['point']['pointStyle'] = 'line';
			$series['elements']['point']['borderWidth'] = 0;
			$series['elements']['point']['hoverBorderWidth'] = 0;
        }

        $this->conf('data.datasets',$this->series);

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
     * Adds a plugin to be loaded.
     *
     * @todo AFAIK this is untested
     * @param string $name Plugin name
     * @return static
     */
    function addPlugin($name)
    {
        $p = $this->conf("plugins")?:[];
        $p[] = "[jscode]$name";
        return $this->conf("plugins",$p);
    }

    /**
     * Get/Set dataset related configuration.
     *
     * @param int|string $index Datase index
     * @param string $name Config name
     * @param mixed $value Optional value
     * @return mixed Returns $this is value is given, else the data requested
     * @throws \Exception
     */
    function dataset($index,$name,$value=null)
	{
        $all = preg_replace('/[*all-]/','',"$index")=="";
        if( $all && $value === null )
            throw new \Exception("Cannot get property '$name' of all datasets");

        foreach( $this->series as $i=>$s )
        {
            //log_debug("[$index] series $i",$s);
            if( $all || ifavail($s,'name') == "$index" || "$index" == "$i" )
            {
                if( $value === null )
                    return $this->conf("data.datasets.$i.$name");

                $this->series[$i][$name] = $value;
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
     * @throws \Exception
     */
    protected function scales($scaleId,$name,$value=null)
    {
        if( $scaleId == "*" )
        {
            if( $value === null )
                throw new \Exception("Cannot get property '$name' of all scales");

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
        return $this->opt("plugins.legend.$name",$value);
    }

    /**
     * Gets/Sets the tooltip.
     *
     * @param string $name Name
     * @param mixed $value Optional value
     * @return mixed Returns $this is value is given, else the data requested
     */
    function tooltip($name,$value=null)
    {
        return $this->opt("plugins.tooltip.$name",$value);
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
        $this->scaleX('type','time');
        if( $unit )
            $this->scaleX("time.unit",$unit);
        if($unit == 'hour')
            $this->scaleX("time.stepSize",2);
        return $this;
    }

    /**
     * Sets Y-Axis to be a percentual scale.
     *
     * @return $this
     */
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
     * @param \ScavixWDF\Base\Color\ColorRange $range Range of colors
     * @return $this
     */
    function setColorRange(\ScavixWDF\Base\Color\ColorRange $range)
    {
        $this->colorRange = $range;
        return $this;
    }

    /**
     * Sets the chart colors.
     *
     * @param array $colors Array of color valus
     * @return $this
     */
    function setColors($colors)
    {
        $this->colors = $colors;
        return $this;
    }

    /**
     * Sets the named chart colors.
     *
     * @param array $colors Associative array of color valus
     * @return $this
     */
    function setNamedColors($colors)
    {
        $this->named_colors = $colors;
        return $this;
    }

    protected function getColor($name=false,$label=false,$value=false)
    {
        if( $value && $this->colorRange )
            return "".$this->colorRange->fromValue($value);
        $col = ifavail($this->named_colors,$name,$label);
        return $col?$col:($this->colors[($this->currentColor++)%count($this->colors)]);
    }

    /**
     * Sets series names.
     *
     * @param array $seriesNames Series names
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
     * @param Closure $pointdatacallback Optional closure receiving $row,$series,$x_value_row_name
     * @return $this
     */
    function setChartData(iterable $data, string $x_value_row, $pointType="StrPoint", $pointdatacallback = false)
    {
        return $this->fill(function($series)use($data, $x_value_row, $pointType, $pointdatacallback)
        {
            $d = [];
            foreach( $data as $r )
            {
                if( $pointdatacallback && is_callable($pointdatacallback) )
                {
                    $v = isset($r[$series])?$r[$series]:0;
                    $d[] = array_merge(ChartJS3::$pointType($r[$x_value_row],floatval($v)), $pointdatacallback($r, $series, $x_value_row));
                }
                elseif (is_callable($pointType))
                {
                    $d[] = $pointType($r, $series, $x_value_row);
                }
                elseif( isset($r[$series]) )
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
     * @param callable|string $pointType Optional classname of the Point handler
     * @param Closure $pointdatacallback Optional closure receiving $row,$series,$series_row_name,$x_value_row_name,$y_value_row_name
     * @return $this
     */
    function setSeriesData(iterable $data, string $series_row, string $x_value_row, string $y_value_row, $pointType="StrPoint", $pointdatacallback = false)
    {
        $series = [];
        foreach( $data as $r )
            $series[] = $r[$series_row];

        $this->setSeries(array_filter(array_unique($series)));
        return $this->fill(function($series)use($data, $series_row, $x_value_row, $y_value_row, $pointType, $pointdatacallback)
        {
            $d = [];
            foreach( $data as $r )
            {
                if( ifavail($r,$series_row) != $series )
                    continue;

                if( $pointdatacallback && is_callable($pointdatacallback) )
                {
                    $v = isset($r[$series])?$r[$series]:0;
                    $d[] = array_merge(ChartJS3::$pointType($r[$x_value_row],floatval($v)), $pointdatacallback($r,$series,$series_row,$x_value_row,$y_value_row));
                }
                elseif( is_callable($pointType) )
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
     * @return static
     */
    function setPieData(array $name_value_pairs)
    {
        $this->series = [];
        return $this->addPieData($name_value_pairs);
    }

    /**
     * Appends data for a Pie chart.
     *
     * @param array $name_value_pairs key-value pairs of data
     * @return static
     */
    function addPieData(array $name_value_pairs)
    {
        if ($this->orderByValue == 'asc')
            asort($name_value_pairs);
        elseif ($this->orderByValue == 'desc')
            arsort($name_value_pairs);

        if ($this->topXOnly > 0 && $this->topXOnly < count($name_value_pairs))
        {
            $chunks = array_chunk($name_value_pairs, $this->topXOnly, true);
            $name_value_pairs = array_shift($chunks);
            $sum = 0;
            foreach ($chunks as $chunk)
                $sum += array_sum($chunk);
            if ($sum > 0)
                $name_value_pairs[$this->topXOthersName] = $sum;
        }

        $labels = []; $this->series[] = ['isPieData'=>true,'data'=>[],'backgroundColor'=>[],'borderColor'=>[]];
        $i = count($this->series)-1;
        foreach( $name_value_pairs as $name=>$val )
        {
            $labels[] = $name;
            $col = $this->getColor($name,false,$val);
            $this->series[$i]['data'][$name] = floatval($val);
            $this->series[$i]['backgroundColor'][$name] = $col;
            $this->series[$i]['borderColor'][$name] = $col;
        }
        return $this->xLabels($labels)->conf('data.datasets',$this->series);
    }

    /**
     * Sets a handler to be called when points are missing.
     *
     * This can be the case when harmonizing data (so that every series has the same X-Values) or when <fillGaps> is called.
     * @param \Closure $missingPointCallback Callback function that receives series_name, x and xval parameters and must return a point
     * @return $this
     */
    function onMissingPoint($missingPointCallback)
    {
        $this->missingPointCallback = $missingPointCallback;
        return $this;
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
        $this->seriesRankByValue = [];
        foreach( $this->series as &$series )
        {
            $series['data'] = $seriesCallback($series['name']);
            if( !$harmonize )
                continue;
            $series_total = 0;
            foreach( $series['data'] as $point )
            {
                $series_total += $point['y'];
                $data[$point['x']]['data'][$series['name']] = $point['y'];
                $data[$point['x']]['xval'] = ifavail($point,'xval','x');
                $data[$point['x']]['total'] = (isset($data[$point['x']]['total']))
                    ?$data[$point['x']]['total']+$point['y']
                    :$point['y'];
            }
            $this->seriesRankByValue[$series['name']] = $series_total;
        }
        if( $harmonize )
        {
            if ($this->orderByValue == 'asc')
            {
                asort($this->seriesRankByValue);
                $this->setSeriesOrder(array_keys($this->seriesRankByValue));
            }
            elseif ($this->orderByValue == 'desc')
            {
                arsort($this->seriesRankByValue);
                $this->setSeriesOrder(array_keys($this->seriesRankByValue));
            }

            $this->xBasedData = $data;
            $this->harmonizeData();
            $this->percentizeData();
        }
		return $this->conf('data.datasets',$this->series);
	}

    protected function sortHarmonizedValues()
    {
        foreach( $this->series as &$series )
            usort($series['data'], function($a,$b)
            {
                $a = ifavail($a,'xval','x');
                $b = ifavail($b,'xval','x');
                return $a<$b?-1:($a>$b?1:0);
            });
    }

    protected function harmonizeData()
    {
        if( !$this->xBasedData || count($this->xBasedData)==0 )
            return false;

        $cb = is_callable($this->missingPointCallback)?$this->missingPointCallback:false;
        foreach( $this->xBasedData as $x=>&$point )
        {
            $xval = ifavail($point,'xval');
            foreach( $this->series as &$series )
            {
                if( $point && isset($point['data'][$series['name']]) )
                    continue;
                $series['data'][] = $cb
                    ?$cb($series['name'],$x,self::phpXVal($xval))
                    :($xval?['x'=>$x,'y'=>null,'xval'=>$xval]:['x'=>$x,'y'=>null]);
            }
        }
        $this->sortHarmonizedValues();

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
                }
                break;
            }
        }
        return true;
    }

    protected static function phpXVal($val)
    {
        if( !$val || !is_numeric($val) )
            return $val;
        if( strlen($val)<13 )
            return $val;
        if( !ends_with("$val","000") )
            return $val;
        return $val / 1000;
    }

    /**
     * Fills all series with contiguous points.
     *
     * @param int|\Closure $increment An integer value or a callback, that receives a X-value and returns the next
     * @return $this
     */
    function fillGaps($increment)
    {
        if( $this->xMin === false || $this->xMax === false )
        {
            foreach( $this->series as $series )
                foreach( $series['data'] as $row )
                {
                    $v = ifavail($row,'xval','x');
                    if( is_numeric($v) )
                        $this->setXMinMax($v);
                }
        }
        if( $this->xMin === false || $this->xMax === false )
        {
            log_warn(__METHOD__,"Unable to detect numeric min/max values");
            return $this;
        }
        $existing = [];
        foreach( $this->series as &$series )
            $existing[$series['name']] = array_map(function($d){ return self::phpXVal(ifavail($d,'xval','x')); },$series['data']);

        $cur = self::phpXVal($this->xMin);
        $max = self::phpXVal($this->xMax);
        $missing = is_callable($this->missingPointCallback)?$this->missingPointCallback:false;
        $end = time()+10;
        while( $cur <= $max )
        {
            foreach( $this->series as &$series )
            {
                if( !in_array($cur, $existing[$series['name']]) )
                    $series['data'][] = $missing?$missing($series['name'],$cur,$cur):['x'=>$cur,'y'=>null];
            }
            if( is_numeric($increment) )
                $cur += $increment;
            elseif( is_callable($increment) )
                $cur = max($increment($cur),$cur);

            if( $end < time() )
            {
                log_warn(__METHOD__,"Running too long, aborting",date("Y-m-d",$cur));
                break;
            }
        }

        $this->sortHarmonizedValues();
        return $this;
    }

    /**
     * @shortcut Create a multi-series time chart
     */
    public static function MultiSeriesTime($data, $dataset_name='series', $x_name='x', $y_name='y')
	{
        $chart = new ChartJS3();
        // todo: use this logik in every place where data is set. then try to create some kind of
        //       detection for automatic ajax-delay-loading
        if( $data instanceof \ScavixWDF\Model\Model || $data instanceof \ScavixWDF\Model\ResultSet )
        {
            $chart->query = $data;
            $data = $data->results();
        }

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
     * @return static
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

    /**
     * Sets if the chart should load it's data via AJAX.
     *
     * @param string $url The data URL
     * @param int $refresh_interval Optional interval to refresh the data (default: -1 = off)
     * @return static
     */
    public function setDelayed($url, $refresh_interval = -1)
    {
        // todo: see MultiSeriesTime for comment on how to delay better
        return $this->opt('refresh',['interval'=>$refresh_interval,'url'=>$url] );
    }

    /**
     * Defines the drawing order of the data series.
     *
     * @param array $names Seriesnames in correct order
     * @return static
     */
    public function setSeriesOrder(array $names)
    {
        $this->series_order = $names;
        return $this;
    }

    protected function reApplyPieData()
    {
        if ($this->conf('type') == 'pie')
        {
            $series = $this->series;
            $this->series = [];
            foreach( $series as $s )
            {
                $nvp = $s['data'];
                $this->addPieData($nvp);
            }
        }
        return $this;
    }

    public function setOrderByValue($ascending=true)
    {
        $this->orderByValue = $ascending ? 'asc' : 'desc';
        return $this->reApplyPieData();
    }

    function setTopXOnly(int $topx = 10, string $others_name = '(others)')
    {
        $this->topXOnly = $topx;
        $this->topXOthersName = $others_name;
        return $this->reApplyPieData();
    }
}
