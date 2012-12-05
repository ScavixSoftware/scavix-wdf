<?
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
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
 
function charting_init()
{
	global $CONFIG;
	$CONFIG['class_path']['system'][] = dirname(__FILE__).'/charting/';

	if( !isset($CONFIG['charting']['datasource']) )
		$CONFIG['charting']['datasource'] = 'system';

	if( !isset($CONFIG['charting']['time_to_life']) )
		$CONFIG['charting']['time_to_life'] = 3600;
}

function charting_generate_hash(&$chart_obj)
{
	if( method_exists($chart_obj,'CreateCacheHash') )
		return $chart_obj->CreateCacheHash();

	$sd = TimeFrame::FirstDate('Ymd');
	$ed = TimeFrame::LastDate('Ymd');
	$br = isset($GLOBALS['BRANDING'])&&$GLOBALS['BRANDING']?$GLOBALS['BRANDING']->id:"";
	$hash = get_class($chart_obj);
	return md5($hash.$sd.$ed.$br);
}

function charting_loadfromcache(&$chart_obj)
{
	global $CONFIG;

	$hash = charting_generate_hash($chart_obj);
	//log_debug("trying to load $chart_obj with hash '$hash'");

	$ds = model_datasource($CONFIG['charting']['datasource']);
	$ds->ExecuteSql("CREATE TABLE IF NOT EXISTS `charting_cache` (
					  `id` varchar(128) NOT NULL,
					  `die_after` datetime default NULL,
					  `content` text,
					  PRIMARY KEY  (`id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	$ds->ExecuteSql("DELETE FROM charting_cache WHERE die_after<=NOW()");

	$entry = $ds->CreateInstance("CacheEntry");
	if( $entry->Load("id=?0",array($hash)) )
	{
		//log_debug(get_class($chart_obj)." loaded from cache","CHART_CACHE");
		$res = str_replace("\r\n","",$entry->content);
		$res = str_replace("\n","",$res);
		return $res;
	}

	return false;
}

function charting_storetocache(&$chart_obj,$content,$timetolife=false)
{
	global $CONFIG;

	$hash = charting_generate_hash($chart_obj);

	//log_debug($CONFIG);
	$ds = model_datasource($CONFIG['charting']['datasource']);
	$entry = $ds->CreateInstance("CacheEntry","id=?0",array($hash));
	$entry->id = $hash;
	if( is_integer($timetolife) )
		$entry->die_after = $ds->Now($timetolife);
	else
		$entry->die_after = $ds->Now($CONFIG['charting']['time_to_life']);
	$entry->content = $content;
	$entry->Save();
	//log_debug(get_class($chart_obj)." stored to cache as $hash","CHART_CACHE");
}


class CacheEntry extends System_Model
{
	function __construct(&$datasource=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['cacheentry']) )
			$CONFIG['model']['cacheentry'] = 'charting_cache';

		parent::__construct($datasource);
	}

	public function GetSchemaDefinition()
	{
		$res  = '
		<table name="'.$this->_table.'">
			<field name="id" type="C" size="128">
				<KEY/>
			</field>
			<field name="die_after" type="T"/>
			<field name="content" type="X"/>
		</table>';
		return $res;
	}
}
?>