<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Google;

use Exception;
use stdClass;

/**
 * This is a google map.
 * 
 * See https://developers.google.com/maps/documentation/javascript/tutorial
 */
class gMap extends GoogleControl
{
	const ROADMAP = 'google.maps.MapTypeId.ROADMAP';
	const SATELLITE = 'google.maps.MapTypeId.SATELLITE';
	const HYBRID = 'google.maps.MapTypeId.HYBRID';
	const TERRAIN = 'google.maps.MapTypeId.TERRAIN';
	
	public $gmOptions = array('language'=>'en','region'=>'DE');
	private $_basicOptions = array('center'=>'new google.maps.LatLng(-34.397, 150.644)','zoom'=>13,'mapTypeId'=>self::ROADMAP,'scrollwheel'=>false);
	private $_markers = [];
	private $_addresses = [];
	
	public $AutoShowHints = false;
	
	/**
	 * @param array $options See https://developers.google.com/maps/documentation/javascript/tutorial#MapOptions
	 */
	function __construct($options=[])
	{
        global $CONFIG;
		parent::__construct('div',false);
        if(isset($CONFIG['google']) && isset($CONFIG['google']['maps']) && isset($CONFIG['google']['maps']['apikey'])) 
            $this->gmOptions['key'] = $CONFIG['google']['maps']['apikey'];

        if(!isset($options['language']) || !isset($options['region']))
        {
            $ci = \ScavixWDF\Localization\Localization::detectCulture();
            if($ci)
            {
                if(!isset($options['region']))
                    $options['region'] = $ci->Region->Code;
                if(!isset($options['language']))
                    $options['language'] = $ci->ResolveToLanguage()->Code;
            }
        }
		$this->gmOptions = array_merge($this->gmOptions,$options);
		$this->_loadApi('maps','3',array('other_params'=>http_build_query($this->gmOptions)));
	}
	
	/**
	 * @override
	 */
	function PreRender($args = [])
	{
		$id = $this->id;
        $this->_basicOptions['center'] = '[jscode]'.$this->_basicOptions['center'];
        $this->_basicOptions['mapTypeId'] = '[jscode]'.$this->_basicOptions['mapTypeId'];
		if( $this->AutoShowHints ) 
			$this->_basicOptions['autoShowHints'] = true;
		$init = array("wdf.gmap.init('$id',".system_to_json($this->_basicOptions).");");
		
		foreach( $this->_markers as $m )
		{
			list($lat,$lng,$opt) = $m;
			$init[] = "wdf.gmap.addMarker('$id',$lat,$lng,".json_encode($opt).");";
		}
		foreach( $this->_addresses as $a )
		{
			if( is_array($a) )
				$init[] = "wdf.gmap.addAddress('$id',".json_encode($a['address']).",".json_encode($a['title']).");";
			else
				$init[] = "wdf.gmap.addAddress('$id',".json_encode($a).");";
		}
    	$init[] = "wdf.gmap.showAllMarkers('$id');";
			
		$this->_addLoadCallback('maps', $init);
		return parent::PreRender($args);
	}
	
	/**
	 * Adds a marker to the map.
	 * 
	 * @param float $lat Latitute
	 * @param float $lng Longitude
	 * @param array $options See https://developers.google.com/maps/documentation/javascript/reference#MarkerOptions
	 * @return static
	 */
	function AddMarker($lat, $lng, $options = [])
	{
		$this->_markers[] = array($lat,$lng,$options);
		return $this;
	}
	
	/**
	 * Shortcut for a named marker.
	 * 
	 * @param float $lat Latitude
	 * @param float $lng Longitude
	 * @param string $title Marker title
	 * @param array $options See https://developers.google.com/maps/documentation/javascript/reference#MarkerOptions
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
	 * Will use googles geolocation to resolve the address to a marker.
	 * @param string $address The address as string
	 * @param string $title An optional title
	 * @return static
	 */
	function AddAddress($address,$title=false)
	{
		$this->_addresses[] = $title?array('address'=>$address,'title'=>$title):$address;
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
		$this->_basicOptions['center'] = "new google.maps.LatLng($lat,$lng)";
		return $this;
	}
	
	/**
	 * Sets the maps type.
	 * 
	 * @param string $type One of gMap::ROADMAP, gMap::SATELLITE, gMap::HYBRID, gMap::TERRAIN
	 * @return static
	 */
	function setType($type)
	{
		$this->_basicOptions['mapTypeId'] = $type;
		return $this;
	}
	
	/**
	 * Sets the maps zoom level.
	 * 
	 * @param int $zoomlevel The initial zoom level
	 * @return static
	 */
	function setZoom($zoomlevel)
	{
		$this->_basicOptions['zoom'] = $zoomlevel;
		return $this;
	}
	
	/**
	 * En-/Disabled the default map UI
	 * 
	 * @param bool $disabled If true UI will be disabled
	 * @return static
	 */
	function setUiDisabled($disabled=false)
	{
		$this->_basicOptions['disableDefaultUI'] = $disabled;
		return $this;
	}
    
    /**
     * Finds a geolocation from a search string.
	 * 
     * @suppress PHP0416
     * @param string $search Search string
	 * @return mixed An object containing formatted_address, latitude and longitude or false on error
     */
    static public function FindGeoLocation($search)
    {
        global $CONFIG;
        if(!isset($CONFIG['google']) || !isset($CONFIG['google']['maps']) || !isset($CONFIG['google']['maps']['apikey']))
            return log_return('missing Google Maps API KEY for '.__FUNCTION__, false);
        
        $ret = new stdClass();
        $geourl = "https://maps.google.com/maps/api/geocode/xml?key=".$CONFIG['google']['maps']['apikey']."&address=".urlencode($search);
        $xmlsrc = file_get_contents($geourl);
        try {
            $xml = simplexml_load_string($xmlsrc);
        }
        catch(Exception $e){
            log_error($geourl."\n".$xmlsrc);
            return false;
        }

        if( strtoupper($xml->status) == 'OK' )
        {
            $ret->formatted_address =  (string) $xml->result->formatted_address;
            $ret->latitude =  (string) $xml->result->geometry->location->lat;
            $ret->longitude = (string) $xml->result->geometry->location->lng;
            return $ret;
        }
        else if( strtoupper($xml->status) == 'ZERO_RESULTS')
            return false;
        else
        {
            log_error($geourl, $xml);
            return false;
        }
    }
}