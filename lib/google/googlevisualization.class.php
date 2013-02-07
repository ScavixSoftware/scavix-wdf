<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

abstract class GoogleVisualization extends GoogleControl implements ICallable
{
	public static $DefaultDatasource = false;
	
	var $_entities = array();
	var $_ds;
	
	var $gvType;
	var $gvOptions;
	var $gvQuery;
	
	static function Make($title=false)
	{
		$className = get_called_class();
		$res = new $className();
		if( $title )
			return $res->opt('title',$title);
		return $res;
	}
	
	function __initialize($type=false,$options=array(),$query=false,$ds=false)
	{
		parent::__initialize();
		$this->addClass('google_vis');
		
		$this->_ds = $ds?$ds:(self::$DefaultDatasource?self::$DefaultDatasource:model_datasource('internal'));
		
		$this->gvType = $type?$type:substr(get_class($this),2);
		$this->gvOptions = $options?$options:array();
		$this->gvQuery = $query;
		
		$this->content("<div class='loading'>&nbsp;</div>");
		store_object($this);
	}
	
	function PreRender($args = array())
	{
		$q = buildQuery($this->id,'Query');
		$opts = json_encode($this->gvOptions);
		$init = "var q = new google.visualization.Query('$q');q.setQuery('{$this->gvQuery}');q.send(function(r){ if(r.isError()){ $('#{$this->id}').html(r.getDetailedMessage()); }else{ var c=new google.visualization.{$this->gvType}($('#{$this->id}').get(0));c.draw(r.getDataTable(),$opts);}});";
		$this->script("google.setOnLoadCallback(function(){ $init });");
		
		if( isset($this->gvOptions['width']) )
			$this->css('width',"{$this->gvOptions['width']}px");
		if( isset($this->gvOptions['height']) )
			$this->css('height',"{$this->gvOptions['height']}px");
		
		return parent::PreRender($args);
	}
	
	protected function _loadPackage($package)
	{
		parent::_loadPackage('visualization','1',$package);
	}
	
	protected function _createMC($ds)
	{
		$paths = explode(PATH_SEPARATOR,ini_get('include_path'));
		$paths[] = __DIR__;
		array_unique($paths);
		ini_set('include_path',implode(PATH_SEPARATOR,$paths));
		require_once('MC/Google/Visualization.php');
		return new MC_Google_Visualization( 
				new PDO($ds->GetDsn(),$ds->Username(),$ds->Password() ), 
				strtolower(get_class($ds->Driver))
			);
	}
	
	protected function _dbTypeToGType($db_type)
	{
		switch( strtolower($db_type) )
		{
			case 'int':
			case 'integer':
				return 'number';
			case 'date':
				return 'date';
			case 'datetime':
				return 'datetime';
		}
		return 'text';
	}
	
	function Query()
	{
		log_debug("{$this->id}->Query()",$_REQUEST,$this);
		$mc = $this->_createMC($this->_ds);
		foreach( $this->_entities as $name=>$spec )
		{
			$mc->addEntity($name, $spec);
			if( !isset($d) ){ $mc->setDefaultEntity($name); $d=true; }
		}
		$mc->handleRequest();
		die("");
	}
	
	function opt($name,$value=null)
	{
		if( is_null($value) )
			return isset($this->gvOptions[$name])?$this->gvOptions[$name]:null;
		$this->gvOptions[$name] = $value;
		return $this;
	}
	
	function setDbQuery($table_name,$query)
	{
		$this->EntityFromTable($table_name);
		$this->gvQuery = $query;
		return $this;
	}
	
	function EntityFromTable($table_name, $alias=false)
	{
		$schema = $this->_ds->Driver->getTableSchema($table_name);
		log_debug($schema);
		$entity = array(
			'table' => $schema->Name,
			'fields' => array()
		);
		foreach( $schema->Columns as $col )
			$entity['fields'][$col->Name] = array(
				'field' => $col->Name,
				'type' => $this->_dbTypeToGType($col->Type),
			);
		
		$this->_entities[$alias?$alias:$table_name] = $entity;
	}
}
