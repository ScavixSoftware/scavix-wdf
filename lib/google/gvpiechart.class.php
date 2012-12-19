<?

class gvPieChart extends GoogleVisualization
{
	function __initialize($options=array(),$query=false,$ds=false)
	{
		parent::__initialize('PieChart',$options,$query,$ds);
		$this->_loadPackage('corechart');
	}
}