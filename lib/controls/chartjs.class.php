<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2017 Scavix Software Ltd. & Co. KG
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
 * @copyright since 2017 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls;

use DateTime;
use ScavixWDF\Base\Control;
use ScavixWDF\Base\DateTimeEx;

/**
 * Represents a Chartt.js chart
 * 
 * @attribute[Resource('Chart.bundle.js')]
 */
class ChartJS extends Control
{
    protected $canvas;
    protected $series = [];
    protected $config = array();

    public static $NAMED_COLORS = [];
    public static $COLORS = ['red','green','blue','yellow','brown'];
    
    public static function TimePoint(DateTime $x,float $y)
    {
        return ['x'=>"[jscode]new Date('".$x->format("c")."')", 'y'=>$y];
    }
    
	function __initialize($type='line')
	{
		parent::__initialize('div');
        $this->css('position','relative')->css("width","100%")->css("height","250px");
        $this->canvas = $this->content(Control::Make('canvas'));
        
        $this->setType('line')->opt('responsive',true)->opt('maintainAspectRatio',false);
	}
    
    function PreRender($args = array())
    {
        //log_debug("CFG",$this->config);
        $this->script("wdf.chartjs.init('{$this->canvas->id}',".system_to_json($this->config).");");
        return parent::PreRender($args);
    }
    
    protected function cfg($part,$name,$value=null)
	{
		if( $value === null )
			return $this->config[$part][$name];
		$this->config[$part][$name] = $value;
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
		return $this->cfg('options',$name,$value);
	}
    
    function setType($type)
    {
        $this->config['type'] = $type;
        return $this;
    }
    
    function setSeries($seriesNames)
    {
        $this->series = [];
        foreach( $seriesNames as $label )
        {
            $borderColor = isset(self::$NAMED_COLORS[$label])
                ?self::$NAMED_COLORS[$label]
                :(self::$COLORS[count($this->series)%count(self::$COLORS)]);
            $this->series[] = compact('label','borderColor');
        }
        return $this;
    }

    function fill($seriesCallback)
	{
        $data = [];
        foreach( $this->series as $series )
        {
            $series['data'] = $seriesCallback($series['label']);
            $data[] = $series;
        }
        $this->series = $data;
		return $this->cfg('data','datasets',$data);
	}
    
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
        
        $chart->opt('scales',['xAxes'=>[['type'=>'time']]]);
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
    
    public function eachSeries($callback)
    {
        foreach( $this->series as &$series )
        {
            $callback($series);
        }
        return $this->cfg('data','datasets',$this->series);
    }
}
