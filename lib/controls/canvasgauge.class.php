<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2017-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls;

use ScavixWDF\Base\Control;

/**
 * Wrapper for canvas-gauges.com
 * 
 * @attribute[Resource('gauge.min.js')]
 */
class CanvasGauge extends Control
{
	function __construct()
	{
		parent::__construct('canvas');
        $this->data('type','radial-gauge')
            ->opt('value-int',0)
            ->opt('value-dec',0)
            ->opt('stroke-ticks',false)
            ->opt('color-border-shadow',false)
            ->opt('color-border-outer','#ccc')
            ->opt('color-border-outer-end',false)
            ->opt('border-outer-width',1)
            ->opt('border-middle-width',0)
            ->opt('border-inner-width',0)
            ->opt('color-value-box-rect','rgba(0,0,0,0)')
            ->opt('color-value-box-rect-end','rgba(0,0,0,0)')
            ->opt('color-value-box-background','rgba(0,0,0,0)')
            ->opt('color-value-box-shadow',false)
            ->opt('font-numbers-size',0)
            ->opt('font-value-size',48)
            ;
	}
    
    /**
     * @shortcut <Control::data>
     */
    function opt($name,$value)
    {
        return $this->data($name, $value);
    }
}
