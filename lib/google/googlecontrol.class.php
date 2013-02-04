<?

/**
 * @attribute[ExternalResource('//www.google.com/jsapi')]
 */
class GoogleControl extends Control
{
	private static $_packages = array();
	private static $_initCodeRendered = false;
	
	function __initialize()
	{
		parent::__initialize('span');
	}
	
	function PreRender($args = array())
	{
		if( !self::$_initCodeRendered )
		{
			self::$_initCodeRendered = true;
			if( count($args) > 0 )
			{
				$controller = $args[0];
				if( $controller instanceof HtmlPage )
				{
					$loader = array();
					foreach( self::$_packages as $api=>$pack )
						foreach( $pack as $version=>$packages )
							$loader[] = "google.load('$api','$version',".json_encode($packages).");";
					$controller->addDocReady($loader,false);
					$controller->addDocReady("google.setOnLoadCallback(function(){ wdf.debug('Google APIs loaded'); });");
				}
			}
		}
		return parent::PreRender($args);
	}
	
	protected function _loadPackage($api,$version,$package)
	{
		self::$_packages[$api][$version]['packages'][] = $package;
	}
}