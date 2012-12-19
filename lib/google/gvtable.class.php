<?

class gvTable extends GoogleVisualization
{
	function __initialize($options=array(),$query=false,$ds=false)
	{
		parent::__initialize('Table',$options,$query,$ds);
		$this->_loadPackage('table');
	}
}