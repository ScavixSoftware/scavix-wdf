<?php
/**
 * Scavix Web Development Framework
 *
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
namespace ScavixWDF;

require_once(__DIR__.'/lessphp/lessc.inc.php');

/**
 * This class creates a unique interface for LESS compilers.
 * 
 * Currently just inherited from lessc, it may be used to add more abstraction
 * when another compiler is used.
 */
class LessCompiler extends \lessc
{
    function get($name)
    {
        try
        {
            return parent::get($name);
        }
        catch(\Exception $ex)
        {
            $msg = $ex->getMessage();
            error_log($msg);
        }
        return ["string","",[]];
    }
}