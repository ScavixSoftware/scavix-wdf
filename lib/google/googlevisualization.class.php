<?

abstract class GoogleVisualization extends GoogleControl implements ICallable
{
	public static $DefaultDatasource = false;
	
	var $_entities = array();
	var $_ds;
	
	var $gvType;
	var $gvOptions;
	var $gvQuery;
	
	function __initialize($type=false,$options=array(),$query=false,$ds=false)
	{
		parent::__initialize();
		
		$this->_ds = $ds?$ds:(self::$DefaultDatasource?self::$DefaultDatasource:model_datasource('internal'));
		
		$this->gvType = $type?$type:substr(get_class($this),2);
		$this->gvOptions = $options?$options:array();
		$this->gvQuery = $query;
		
		$this->content("TXT_GV_LOADING");
		store_object($this);
	}
	
	function PreRender($args = array())
	{
		$q = buildQuery($this->id,'Query');
		$opts = json_encode($this->gvOptions);
		$init = "var q = new google.visualization.Query('$q');q.setQuery('{$this->gvQuery}');q.send(function(r){ if(r.isError()){ $('#{$this->id}').html(r.getDetailedMessage()); }else{ var c=new google.visualization.{$this->gvType}($('#{$this->id}').get(0));c.draw(r.getDataTable(),$opts);}});";
		$this->script("google.setOnLoadCallback(function(){ $init });");
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
