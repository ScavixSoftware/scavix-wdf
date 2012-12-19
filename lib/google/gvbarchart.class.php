<?

class gvBarChart extends GoogleVisualization
{
	function __initialize($options=array(),$query=false,$ds=false)
	{
		parent::__initialize('BarChart',$options,$query,$ds);
		$this->_loadPackage('corechart');
	}
}