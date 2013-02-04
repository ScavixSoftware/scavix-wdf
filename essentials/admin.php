<?php

function admin_init()
{
	global $CONFIG;
	
	if( $CONFIG['system']['admin']['enabled'] )
	{
		$CONFIG['class_path']['system'][]   = __DIR__.'/admin/';
		if( !$CONFIG['system']['admin']['username'] || !$CONFIG['system']['admin']['username'] )
			throw new WdfException("System admin needs username and password to be set!");
		
		$CONFIG['system']['admin']['actions'] = array();
	}
}

function admin_register_handler($label,$controller,$method)
{
	global $CONFIG;
	$CONFIG['system']['admin']['actions'][$label] = array($controller,$method);
}