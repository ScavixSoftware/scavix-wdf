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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls;

use ScavixWDF\Base\Control;

/**
 * Datamap wrapper for https://github.com/markmarkoh/datamaps
 * 
 * @attribute[Resource('d3.min.js')]
 * @attribute[Resource('topojson.min.js')]
 * @attribute[Resource('datamaps.world.min.js')]
 */
class Datamap extends Control
{
    protected $map_title, $canvas, $colorRange;
    protected $config = [];
    
	function __initialize($title="",$height=600)
	{
        parent::__initialize('div');
        $this->class = 'wdf-datamap';
        $this->map_title = Control::Make('div')->append($title)->addClass('caption')->appendTo($this);
        $this->canvas = Control::Make('div')
            ->css('position','relative')
            ->css("text-align","center")
            ->appendTo($this);
        
        $this->conf('geographyConfig.popupTemplate',"function(geography, data){return '<div class=\"hoverinfo\">'+(data.tooltip||geography.properties.name)+'</div>'; }");
        $this->conf("geographyConfig.highlightOnHover",false);
        $this->conf("projection","mercator");
        $this->conf("height",intval($height));
    }
    
    function PreRender($args = array())
    {
        //log_debug(__METHOD__,$this->config);
        $this->config['element'] = "[jscode]document.getElementById('{$this->canvas->id}')";
        if( $this->colorRange )
            $this->script("new Datamap(".system_to_json($this->config).");");
        else
            $this->script("new Datamap(".system_to_json($this->config).").legend();");
        return parent::PreRender($args);
    }
    
    function conf($name,$value=null)
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
    
    function setColor($country_code, $color, $tooltip=false)
    {
        $code = \ScavixWDF\Localization\Localization::convert_countrycode($country_code);
        if( !$tooltip )
            $tooltip = _text(strtoupper("TXT_COUNTRY_{$country_code}"));
        return $this->conf("data.$code",["fillKey"=>"$code","tooltip"=>"$tooltip"])
            ->conf("fills.$code","".$color);
    }
    
    function setColorRange(\ScavixWDF\Base\Color\ColorRange $range)
    {
        $h = intval($this->conf("height"));
        $grad = '<linearGradient id="legend_gradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:'.$range->from.';stop-opacity:1"/>
            <stop offset="100%" style="stop-color:'.$range->to.';stop-opacity:1"/>
        </linearGradient>';
        $leg  = '<rect x="0" y="'.($h-30).'" width="220" height="20" fill="url(#legend_gradient)"></rect>';
        $leg .= '<text x="0" y="'.($h-35).'">'.$range->min.'</text>';
        $leg .= '<text x="220" y="'.($h-35).'" text-anchor="end">'.$range->max.'</text>';
        
        $this->colorRange = $range;
        $done  = "d3.select('#{self} svg').append('defs').html(".json_encode($grad).");";
        $done .= "d3.select('#{self} svg').append('g').html(".json_encode($leg).");";
        //$done = "";
        $this->conf("done","function(){ $done }");
        return $this;
    }
    
    function setValue($country_code, $value, $tooltip=false)
    {
        if( !$this->colorRange )
            \ScavixWDF\WdfException::Raise("Please set a ColorRange before calling setValue");
        $color = "".$this->colorRange->fromValue($value);
        if( !$tooltip )
            $tooltip = _text(strtoupper("TXT_COUNTRY_{$country_code}")).": $value";
        return $this->setColor($country_code, $color, $tooltip);
    }
}
