<?

class gvColumnChart extends GoogleVisualization
{
	function __initialize($options=array(),$query=false,$ds=false)
	{
		parent::__initialize('ColumnChart',$options,$query,$ds);
		$this->_loadPackage('corechart');
	}
}