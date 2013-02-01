<?

/**
 * @attribute[Resource('jquery.js')]
 */
abstract class Renderable 
{
	var $_translate = true;
	var $_storage_id;
	var $_content = array();
	var $_script = array();

	abstract function WdfRenderAsRoot();
	abstract function WdfRender();
	
	function __getContentVars(){ return array('_content'); }
	
	function __collectResources()
	{
		global $CONFIG;
		
		$min_js_file = isset($CONFIG['use_compiled_js'])?$CONFIG['use_compiled_js']:false;
		$min_css_file = isset($CONFIG['use_compiled_css'])?$CONFIG['use_compiled_css']:false;
		
		if( $min_js_file && $min_css_file )
			return array($min_css_file,$min_js_file);

		$res = $this->__collectResourcesInternal($this);
		if( !$min_js_file && !$min_css_file )
			return $res;
		
		$js = array(); $css = array();
		foreach( $res as $r )
		{
			if( ends_with($r, '.js') )
			{
				if( !$min_js_file )
					$js[] = $r;
			}
			else
			{
				if( !$min_css_file )
					$css[] = $r;
			}
		}
		
		if( $min_js_file )
		{
			$css[] = $min_js_file;
			return $css;
		}
		
		$js[] = $min_css_file;
		return $js;
	}
	
	private function __collectResourcesInternal($template)
	{
		$res = array();
		
		if( is_object($template) )
		{
			$classname = strtolower(get_class($template));
//			log_debug("$classname OBJECT",gettype($template));
			
			// first collect statics from the class definitions
			$static = ResourceAttribute::ResolveAll( ResourceAttribute::Collect($classname) );
			$res = array_merge($res,$static);
//			log_debug("$classname STATIC NEW",$static);
			
			if( $template instanceof Renderable )
			{
				// then check all contents and collect theis includes
				foreach( $template->__getContentVars() as $varname )
				{
					$sub = array();
					foreach( $template->$varname as $var )
					{
						if( is_object($var)|| is_array($var) )
							$sub = array_merge($sub,$this->__collectResourcesInternal($var));
					}
					$res = array_merge($res,$sub);
//					log_debug("$classname CONTENT $varname",$sub);
				}
				
				// finally include the 'self' stuff (<classname>.js,...)
				// Note: these can be forced to be loaded in static if they require to be loaded before the contents resources
				$parents = array();
				do
				{
					if( resourceExists("$classname.css") )
						$parents[] = resFile("$classname.css");
					if( resourceExists("$classname.js") )
						$parents[] = resFile("$classname.js");
					$classname = strtolower(get_parent_class($classname));
				}
				while($classname != "");// && $classname != "template" && $classname != "control" && $classname != "controlextender"); // todo: check if this abort condition is correct
				$res = array_merge($res,array_reverse($parents));
//				log_debug(strtolower(get_class($template))." PARENTS",$parents);
			}
		}
		elseif( is_array($template) )
		{
			foreach( $template as $var )
			{
				if( is_object($var)|| is_array($var) )
					$res = array_merge($res,$this->__collectResourcesInternal($var));
			}
		}
		return array_unique($res);
	}
}

?>
