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

use ScavixWDF\Base\Control;

/**
 * Represents a Chartt.js chart
 * 
 * @attribute[Resource('Chart.bundle.js')]
 */
class ChartJS extends Control
{
    protected $datasets = [];
    protected $config = array();
    
	function __initialize()
	{
		parent::__initialize('canvas');
	}
    
    function PreRender($args = array())
    {
        $this->script("wdf.chartjs.init('{$this->id}',".system_to_json($this->config).");");
        return parent::PreRender($args);
    }
    
    protected function cfg($part,$name,$value=null)
	{
		if( $value === null )
			return $this->config[$part][$name];
		$this->config[$part][$name] = $value;
		return $this;
	}
    
    function setType($type)
    {
        $this->config['type'] = $type;
        return $this;
    }
    
	function setXMarkers($marker=[])
	{
		return $this->cfg('data','labels',array_values($marker));
	}
    
    public static $COLORS = ['red','green','blue','yellow','brown'];
    function addDataset($label,$borderColor=false,$fill=false,$spanGaps=true)
    {
        if( $borderColor == false )
            $borderColor = self::$COLORS[ count($this->datasets)%count(self::$COLORS) ];
        $this->datasets[] = compact('label','borderColor','fill','spanGaps');
        return $this;
    }

    function fill($valueCallback)
	{
        $data = [];
        foreach( $this->datasets as $dataset )
        {
            $dataset['data'] = [];
            foreach( $this->cfg('data','labels') as $xmark )
                $dataset['data'][] = $valueCallback($dataset['label'],$xmark);
            $data[] = $dataset;
        }
		return $this->cfg('data','datasets',$data);
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
}
