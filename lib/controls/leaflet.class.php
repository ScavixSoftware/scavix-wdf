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
            'scrollWheelZoom' => false
        ];

    protected $_markers = [];
    protected $_addresses = [];
    protected $_polygons = [];
    protected $autoZoom = true;

    public $AutoShowHints = false; // useless gMap compat

    public $TileProvider = 'openstreetmap';
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
    
	function __construct()
	{
		parent::__construct("div");
        $this->css("height", '300px')->css("width", '400px')->css("max-width", '100%');
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

    /**
     * @override
     */
    function PreRender($args = [])
    {
        $map = "$('#{self}').data('leaflet')";
        // initialize
        $opts = system_to_json($this->Options);
        $this->script("$('#{self}').data('leaflet',L.map('{self}',$opts));");
        if( $this->autoZoom )
            $this->script("$map.on('layeradd',function(e){ if( !e.layer._latlng && !e.layer._latlngs )return; var b=[]; this.eachLayer(function(l){ if( l._latlng ) b.push(l._latlng); for(var i in l._latlngs||{}) b = b.concat(l._latlngs[i]); }); if( b.length ) { this.fitBounds(b); if(b.length == 1) { this.setZoom(".$this->Options['zoom']."); }; }; });");

        // set tileLayer
        $opts = self::$providers[$this->TileProvider];
        $url = $opts['url']; unset($opts['url']);
        $opts['crossOrigin'] = '';
        $opts = system_to_json($opts);
        
        $this->script("L.tileLayer('$url',$opts).addTo($map);");
        // $markers = "$('#{self}').data('markers')";
        if($this->_markers)
            $this->script("var markers = new Array();");

        // add polygons
        foreach( $this->_polygons as $polygon )
        {
            $points = [];
            foreach( $polygon['points'] as $point )
            {
                $lat = ifavail($point,'lat','latitude','Latitude','Lat');
                $lng = ifavail($point,'lng','longitude','Longitude','Lng');
                if( !$lat )
                    $lat = array_shift($point);
                if( !$lng )
                    $lng = array_shift($point);
                $points[] = [$lat,$lng];
            }
            $points = json_encode($points);
            $this->script("p = new L.polygon($points,{color:'{$polygon['color']}'}).addTo($map);");
            //$this->script("$map.fitBounds(p.getBounds());");
        }
        
        // add markers
        foreach( $this->_markers as $m )
        {
            $opts = json_encode($m[2]);
            $title = urlencode($m[2]['title']);
            $this->script("markers.push(L.marker([{$m[0]},{$m[1]}],$opts).bindPopup('{$title}').addTo($map));");
        }

        // add addresses
        $q = "https://nominatim.openstreetmap.org/search";
        $prms = ['format'=>'json','limit'=>1];
        foreach( $this->_addresses as $a )
        {
            list($address,$title,$report_back_url) = $a;
            $prms['q'] = $address;
            $tooltip = html_entity_decode(strip_tags(str_replace(["\r", "\n", "'"], ["\\r", "\\n", "\\'"], $title)));
            $popup = str_replace(["\r\n", "\r", "\n", "'"], ["<br/>", "", "", "\\'"], $title);
            $cb = "if(r.length > 0) { al = true; {$map}.eachLayer(function(l) { if(typeof(l._latlng) != 'undefined') { if((l._latlng.lat == r[0].lat) && (l._latlng.lng == r[0].lon)) { al = false; }};}); if(!al) return; L.marker([r[0].lat,r[0].lon],{title:'$tooltip'||r[0].display_name}).bindPopup('$popup'||r[0].display_name).addTo($map); }";
            $city = '';
            if(strpos($address, ', ') !== false)
                $city = array_last(explode(', ', $address));
            if($city != '')
                $cb .= " else { wdf.get('$q',".json_encode(['format'=>'json','limit'=>1,'q' => $city]).",function(r){ $cb }); }";

            if ($report_back_url)
                $cb = "$.ajax({type:'POST',url:'$report_back_url',data:JSON.stringify(r),contentType:'application/json; charset=utf-8',dataType:'JSON'}); $cb";
            $this->script("wdf.get('$q',".json_encode($prms).",function(r){ $cb; });");
        }
        if($this->_markers)
            $this->script("$('#{self}').data('markers', markers);");
        
//        $this->script("if(L.marker.length == 0) { $('#{self}').hide(); };");

        parent::PreRender($args);
    }

    /**
     * Sets the tile provider.
     * 
     * @param string $name The provider name
     * @return static
     */
    function setTileProvider($name)
    {
        if( !isset(self::$providers[$name]) )
            \ScavixWDF\WdfException::Raise("Unknown provider '$name'. Use one of: ".implode(",", array_keys(self::$providers)));

        $this->TileProvider = $name;
        return $this;
    }

    /**
     * En-/Disables auto zooming.
     * 
     * @param bool $on If true on, else off
     * @return static
     */
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
     * @param array $options Optional options
     * @return static
     */
    function AddMarker($lat, $lng, $options = [])
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
     * @param array $options Optional options
     * @return static
     */
    function AddMarkerTitled($lat, $lng, $title, $options = [])
    {
        $options['title'] = $title;
        $this->_markers[] = array($lat,$lng,$options);
        return $this;
    }

    /**
     * Adds an address to the map.
     *
     * Will use geolocation API to resolve the address to a marker.
     * @param string $address The address as string
     * @param string $title An optional title
     * @param bool|string $report_back_url URL to post detection results to (from Javascript) or false to disable
     * @return static
     */
    function AddAddress($address,$title=false,$report_back_url=false)
    {
        $this->_addresses[] = [$address,$title?:'',$report_back_url];
        return $this;
    }
    
    /**
     * Add a polygon.
     * 
     * @param string $color HTML color
     * @param array $points Array of points
     * @return static
     */
    function AddPolygon($color,$points)
    {
        $this->_polygons[] = ['color'=>$color, 'points'=>$points];
        return $this;
    }

    /**
     * Sets the maps center point.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return static
     */
    function setCenterPoint($lat,$lng)
    {
        return $this->opt('center',[$lat,$lng]);
    }

    /**
     * Sets the maps zoom level.
     *
     * @param int $zoomlevel The initial zoom level
     * @return static
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

    /**
     * @deprecated
     */
    function setType($type) { return $this->_voidHint(); }

    /**
     * Disables UI controls.
     * 
     * @param bool $disabled If true disabled, else not
     * @return static
     */
    function setUiDisabled($disabled=false)
    {
        return $this->opt('zoomControl',!$disabled);
    }

    /**
     * Find a location using the OpenStreetMap API.
     * 
     * @param string $search Search text
     * @param string $sRef Optional referrer string to send with the query
     * @return bool|stdClass
     */
    static public function FindGeoLocation($search, $sRef = false)
    {
        global $CONFIG;
        $geourl = 'https://nominatim.openstreetmap.org/search?format=json&polygon=1&addressdetails=1&q=' . urlencode($search);
        
        if($sRef === false)
            $sRef = $CONFIG['system']['url_root'];
        
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
            $oData = false;
            if($aRes !== false){
                if(is_array($aRes) && count($aRes) === 1) {
                    $oData = $aRes[0];
                }elseif(count($aRes) > 1){
                    /**
                     * more than 1 place found, check if the is one osm_type "way
                     */
                    foreach ($aRes as $oPlace) {
                        if($oPlace->osm_type === 'way'){
                            $oData = $oPlace;
                            break;
                        }
                    }
                }else{
                    return false;
                }
                if($oData !== false){
                    $oRet = new stdClass();
                    $oRet->formatted_address = (string)$oData->display_name;
                    $oRet->latitude = (string)$oData->lat;
                    $oRet->longitude = (string)$oData->lon;
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
