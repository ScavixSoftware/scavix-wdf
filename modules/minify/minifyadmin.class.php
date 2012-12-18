<?

class MinifyAdmin extends SysAdmin
{
	/**
	 * @attribute[RequestParam('submitter','bool',false)]
	 * @attribute[RequestParam('skip_minify','bool',false)]
	 * @attribute[RequestParam('random_nc','bool',false)]
	 */
	function Start($submitter,$skip_minify,$random_nc)
	{
		global $CONFIG;
		
		if( !$submitter )
		{
			$this->addContent("<h1>Select what to minify</h1>");
			$form = $this->addContent( new Form() );
			$form->AddHidden('submitter','1');
			$form->AddCheckbox('skip_minify','Skip minify (only collect and combine)<br/>');
			$form->AddCheckbox('random_nc','Generate random name (instead of app version)<br/>');
			$form->AddSubmit('Go');
			return;
		}
		
		$this->addContent("<h1>Minify DONE</h1>");
		$parts = array_diff($CONFIG['class_path']['order'], array('system','model','content'));
		$paths = array();
		foreach( $parts as $part )
			$paths = array_merge ($paths,$CONFIG['class_path'][$part]);
		
		sort($paths);
		$root_paths = array();
		foreach( $paths as $i=>$cp )
		{
			$root = true;
			for($j=0; $j<$i && $root; $j++)
				if(starts_with($cp, $paths[$j]) )
					$root = false;
			if( $root )
				$root_paths[] = $cp;
		}
		
		if( $skip_minify )
			$_GET['nominify'] = '1';
		
		$target_path = cfg_get('minify','target_path');
		system_ensure_path_ending($target_path,true);
		$target = $target_path.cfg_get('minify','base_name');
		minify_all($root_paths, $target, $random_nc?md5(time()):getAppVersion('nc'));
	}
}