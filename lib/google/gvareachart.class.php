<?

class gvAreaChart extends GoogleVisualization
{
	function __initialize($options=array(),$query=false,$ds=false)
	{
		parent::__initialize('AreaChart',$options,$query,$ds);
		$this->_loadPackage('corechart');
	}
}