<?

class Sample extends HtmlPage
{
	function Index()
	{
		$this->set("title","Hello world!");
		$this->set("test","subtitle");
		
		$tab = new Table();
		$tab->NewRow(array('1','2','3', new uiButton("jQuery UI button")));
		$this->set("table",$tab);
	}
}