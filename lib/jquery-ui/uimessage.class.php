<?php

class uiMessage extends uiTemplate
{
	static function Hint($message)
	{
		if( function_exists('translation_string_exists') && translation_string_exists($message) )
			$message = getString($message);
		
		$res = new uiMessage();
		$res->set('message',$message);
		return $res;
	}
	
	static function Error($message)
	{
		$res = uiMessage::Hint($message);
		$res->set('type','error');
		return $res;
	}
	
	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}
}
