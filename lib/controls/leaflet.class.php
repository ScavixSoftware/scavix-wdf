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
use stdClass;

/**
 * HTML anchor element
 *
 * Wraped as control to allow to inherit from this class and add code for AJAX handling in that derivered classes.
 */
class LeafLet extends Control
{
    protected $Options =
        [
            'center' => [52.98, 10.57],
            'zoom' => 7,
        ];

    protected $_markers = [];
    protected $_addresses = [];
    protected $autoZoom = true;

    var $AutoShowHints = false; // useless gMap compat

    var $TileProvider = 'openstreetmap';
    protected static $providers =
    [
        'openstreetmap' =>
            [
                'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            ],
        'openstreetmap_bw' =>
            [
                'url' => 'http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            ],
        'openstreetmap_hot' =>
            [
                'url' => 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
                'attribution' => '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, Tiles courtesy of <a href="http://hot.openstreetmap.org/" target="_blank">Humanitarian OpenStreetMap Team</a>',
            ],
        'opentopomap' =>
            [
                'url' => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                'attribution' => 'Map data: &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
            ],
        'openmapsurfer_roads' =>
            [
                'url' => 'https://korona.geog.uni-heidelberg.de/tiles/roads/x={x}&y={y}&z={z}',
                'attribution' => 'Imagery from <a href="http://giscience.uni-hd.de/">GIScience Research Group @ University of Heidelberg</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            ],
        'esri_worldstreetmap' =>
            [
                'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
                'attribution' => 'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012',
            ],
    ];
    
	function __initialize()
	{
		parent::__initialize("div");
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
        if( $value === null )
            return $this->Options[$name];
        //log_trace($this->id.': '.$name.' > ',$value);
        $this->Options[$name] = $value;
        return $this;
    }

    function PreRender($args = array())
    {
        $map = "$('#{self}').data('leaflet')";
        // initialize
        $opts = system_to_json($this->Options);
        $this->script("$('#{self}').data('leaflet',L.map('{self}',$opts));");
        if( $this->autoZoom )
            $this->script("$map.on('layeradd',function(e){ if( !e.layer._latlng ) return; var b=[]; this.eachLayer(function(l){ if( l._latlng ) b.push(l._latlng); }); if( b.length ) { this.fitBounds(b); } if(b.length == 1) { this.setZoom(".$this->Options['zoom']."); } });");

        // set tileLayer
        $opts = self::$providers[$this->TileProvider];
        $url = $opts['url']; unset($opts['url']);
        $opts = system_to_json($opts);
        $this->script("L.tileLayer('$url',$opts).addTo($map);");

        // add markers
        foreach( $this->_markers as $m )
        {
            $opts = json_encode($m[2]);
            $this->script("L.marker([{$m[0]},{$m[1]}],$opts).bindPopup('{$m[2]['title']}').addTo($map);");
        }

        // add addresses
        $q = "https://nominatim.openstreetmap.org/search";
        $prms = ['format'=>'json','limit'=>1];
        foreach( $this->_addresses as $a )
        {
            list($address,$title) = $a;
            $prms['q'] = $address;
            $cb = "L.marker([r[0].lat,r[0].lon],{title:'$title'||r[0].display_name}).bindPopup('$title'||r[0].display_name).addTo($map);";
            $this->script("wdf.get('$q',".json_encode($prms).",function(r){ $cb });");
        }

        parent::PreRender($args);
    }

    function setTileProvider($name)
    {
        if( !isset(self::$providers[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown provider '$name'. Use one of: ".implode(",", array_keys(self::$providers)));

        $this->TileProvider = $name;
        return $this;
    }

    function setAutoZoom($on)
    {
        $this->autoZoom = $on;
        return $this;
    }

    /**
     * Adds a marker to the map.
     *
     * @param float $lat Latitute
     * @param float $lng Longitude
     * @param array $options See https://developers.google.com/maps/documentation/javascript/reference#MarkerOptions
     * @return LeafLet
     */
    function AddMarker($lat, $lng, $options = array())
    {
        if( !isset($options['title']) )
            $options['title'] = "Lat: $lat, Lng: $lng";
        $this->_markers[] = [$lat,$lng,$options];
        return $this;
    }

    /**
     * Shortcut for a named marker.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $title Marker title
     * @param array $options See https://developers.google.com/maps/documentation/javascript/reference#MarkerOptions
     * @return LeafLet
     */
    function AddMarkerTitled($lat, $lng, $title, $options = array())
    {
        $options['title'] = $title;
        $this->_markers[] = array($lat,$lng,$options);
        return $this;
    }

    /**
     * Adds an address to the map.
     *
     * Will use googles geolocation to resolve the address to a marker.
     * @param string $address The address as string
     * @param string|false $title An optional title
     * @return LeafLet
     */
    function AddAddress($address,$title=false)
    {
        $this->_addresses[] = [$address,$title?:''];
        return $this;
    }

    /**
     * Sets the maps center point.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return mixed
     */
    function setCenterPoint($lat,$lng)
    {
        return $this->opt('center',[$lat,$lng]);
    }

    /**
     * Sets the maps zoom level.
     *
     * @param int $zoomlevel The initial zoom level
     * @return mixed
     */
    function setZoom($zoomlevel)
    {
        return $this->opt('zoom',$zoomlevel);
    }

    private function _voidHint()
    {
        log_warn(__METHOD__,"This method does nothing. It's just there to be compatible with the gMap control.");
        return $this;
    }

    function setType($type) { return $this->_voidHint(); }

    function setUiDisabled($disabled=false)
    {
        return $this->opt('zoomControl',!$disabled);
    }

    /**
     * @param $search
     * @param string $sRef
     * @return bool|stdClass
     */
    static public function FindGeoLocation($search, $sRef = 'https://www.scavix.com')
    {
        $geourl = 'https://nominatim.openstreetmap.org/search?format=json&polygon=1&addressdetails=1&q=' . urlencode($search);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $geourl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        /**
         * nominatim needs valid referer
         */
        curl_setopt($curl, CURLOPT_REFERER, $sRef);
        $res = curl_exec($curl);
        if (curl_error($curl))
            return false;
        $curlinfo = curl_getinfo($curl);
        if($curlinfo['http_code'] === 200){
            $aRes = json_decode($res);
            if($aRes !== false){
                if(is_array($aRes) && count($aRes) === 1){
                    $oData = $aRes[0];
                    $oRet = new stdClass();
                    $oRet->formatted_address =  (string) $oData->display_name;
                    $oRet->latitude =  (string) $oData->lat;
                    $oRet->longitude = (string) $oData->lon;
                    return $oRet;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}
