<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls\Form;

/**
 * This is an &lt;input type=button/&gt;.
 *
 */
class Button extends Input
{
	/**
	 * Creates a Button.
	 *
	 * Note that you can safely ignore all but the $label argument if your new button
	 * shall not redirect elsewhere on click.
	 * @param string $label Label text
	 * @param string $controller Controller for click redirect
	 * @param string $event Event for click redirect
	 * @param mixed $data Data for click redirect
	 */
	function __construct( $label, $controller="", $event="", $data="")
	{
		parent::__construct();
		$this->setType("button");
        $this->Tag = 'button';
        $this->content($label);

		if( ($controller != "") && (strpos($controller,"$") === false) && (strpos($controller,"?") === false) && !starts_with($controller, 'wdf.') )
			$query = "wdf.redirect('".buildQuery($controller,$event,$data)."')";
		else
			$query = $controller;

		if( $query != "" )
			$this->onclick = $query;
	}

	/**
	 * Overrides <Control::Make> with own logic.
	 *
     * @deprecated (2021/05) This hides the parent Make method and should not be used anymore
	 * @param string $label Label
	 * @param string $onclick OnClick JS code
	 * @return Button The new button
	 */
	static function Make(...$args)
	{
        log_debug(__METHOD__,"Deprecated. Use Button::Textual instead");
        $label = isset($args[0])?$args[0]:'';
        $onclick = isset($args[1])?$args[1]:'';

		$res = new Button($label);
		if( $onclick ) $res->onclick = $onclick;
		return $res;
	}

    /**
	 * Button creation shortcut
	 *
	 * @param string $label Label
	 * @param string $onclick OnClick JS code
	 * @return Button The new button
	 */
	static function Textual($label, $onclick = false)
	{
		$res = new Button($label);
		if ($onclick)
		{
			if(starts_iwith($onclick, 'http://') || starts_iwith($onclick, 'https://'))
				$onclick = "document.location.href = decodeURIComponent('".urlencode($onclick)."');";
			$res->onclick = $onclick;
		}
		return $res;
	}

	/**
	 * Creates javascript code to redirect elsewhere on button click.
	 *
 	 * @param mixed $controller The controller to be loaded (can be <Renderable> or string)
	 * @param string $method The method to be executed
	 * @param array|string $data Optional data to be passed
	 * @return static
	 */
	function LinkTo($controller,$method='',$data=[])
	{
		$q = buildQuery($controller,$method,$data);
		$this->onclick = "document.location.href = '$q';";
		return $this;
	}
}
