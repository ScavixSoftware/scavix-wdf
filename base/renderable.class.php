<?
 
abstract class Renderable 
{
	var $_translate = true;
	var $_storage_id;
	var $_content = array();
	var $_script = array();

	abstract function WdfRenderAsRoot();
	abstract function WdfRender();
	
	function __getContentVars(){ return array('_content'); }
	
	function __collectResources($template=false)
	{
		if( $template === false ) 
			$template = $this;
		
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
							$sub = array_merge($sub,$this->__collectResources($var));
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
					$res = array_merge($res,$this->__collectResources($var));
			}
		}
		return array_unique($res);
	}
}

?>
