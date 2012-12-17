<?

class MinifyAdmin extends SysAdmin
{
	/**
	 */
	function StartCss()
	{
		global $CONFIG;
		$this->addContent("<h1>Select what to minify</h1>");
		
		$parts = array_diff($CONFIG['class_path']['order'], array('system','model','content'));
		$paths = array();
		foreach( $parts as $part )
			$paths = array_merge ($paths,$CONFIG['class_path'][$part]);
		
		sort($paths);
		$ul = $this->addContent( new Control('ul') );
		foreach( $paths as $i=>$cp )
		{
			$root = true;
			for($j=0; $j<$i && $root; $j++)
				if(starts_with($cp, $paths[$j]) )
					$root = false;
			if( $root )
				$ul->content( "<li>$cp</li>" );
		}
	}
}