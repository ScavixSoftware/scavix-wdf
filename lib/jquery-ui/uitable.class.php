<?php

/**
 * @attribute[Resource('jquery-ui/jquery-ui.js')] 
 * @attribute[Resource('jquery-ui/jquery-ui.css')] 
 */
class uiTable extends Table
{
	function __initialize()
	{
		parent::__initialize();
		$this->RenderMode = self::RENDER_MODE_JQUERYUI;
	}
}
