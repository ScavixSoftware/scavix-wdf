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
 *  This is a &lt;input type='text'/&gt;.
 * 
 */
class TextInput extends Input
{
	/**
	 * @param string $value A value
	 * @param string $name An optional name
	 * @param string $class An optional CSS class
	 */
    function __construct($value=false,$name=false,$class=false)
	{
		parent::__construct();
		$this->setType("text")->setValue($value)->setName($name);
		if( $class )
			$this->class = $class;
	}
    
    /**
     * Enables form auto-submission on value change.
     * 
     * This is done by submitting `$(this).closest('form')`
     * 
     * @param int $delay Delay in milliseconds
     * @return static
     */
    function setAutoSubmit($delay=500)
    {
        if( $delay )
        {
            $s = 'var l = e.data("lastvalue")||""; if( e.val() == l ) return; e.data("lastvalue",e.val()); e.closest("form").submit();';
            $s = "var e = $(this); clearTimeout(wdf.{$this->id}_keyup); wdf.{$this->id}_keyup = setTimeout(function(){ $s },$delay);";
            $this->attr("onkeyup","$s");
        }
        else
            $this->attr("onkeyup","");
        return $this;
    }
}
