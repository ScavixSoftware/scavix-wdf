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
namespace ScavixWDF\Controls\Table;

use ScavixWDF\Base\Control;

/**
 * Just a table cell (td).
 * 
 */
class Td extends Control
{
    public $options = false;
	public $CellFormat = false;
	
	public $colspan = "";

	/**
	 * @param array $options No valid options defined yet
	 */
	function __construct($options=false)
	{
		parent::__construct("div");
		$this->class = "td";
        $this->options = $options;
	}

	/**
	 * Returns this cells contents.
	 * 
	 * Note: This will return the unformatted content and ignore an eventually active <CellFormat>.
	 * @return string Contents
	 */
	function GetContent()
	{
		$tmp = clone $this;
		$tmp->Tag = "";
		return $tmp->WdfRender();
	}

	/**
	 * Set new content.
	 * 
	 * @param mixed $content The new content
	 * @return void
	 */
	function SetContent($content)
	{
        if($content instanceof \ScavixWDF\Base\Renderable)
        {
            $table = $this->closest('Table');
            if($table !== false)
                $table->force_ajax_dependenciesloading = true;
        }
		$this->content($content,true);
	}
}
