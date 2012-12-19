<?

class gvAreaChart extends GoogleVisualization
{
	function __initialize()
	{
		parent::__initialize();
		$this->_loadPackage('corechart');
//		$this->_loadPackage('table');
//		$this->gvType = "Table";
	}
}