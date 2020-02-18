<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Admin;

use ScavixWDF\Controls\Form\Form;

/**
 * SysAdmin module to manage minified JS/CSS.
 * 
 * @attribute[NoMinify]
 */
class MinifyAdmin extends SysAdmin
{
	/**
     * @internal Starts the minify process.
     * 
	 * @attribute[RequestParam('submitter','bool',false)]
	 * @attribute[RequestParam('skip_minify','bool',false)]
	 * @attribute[RequestParam('random_nc','bool',false)]
	 */
	function Start($submitter,$skip_minify,$random_nc)
	{
		if( !$submitter )
		{
            if( preg_match('/^PHP\s.*Development Server$/',$_SERVER['SERVER_SOFTWARE']) )
            {
                \ScavixWDF\JQueryUI\uiMessage::Error("This page is driven by PHP Develompent Server, that cannot handle PHP self-driven requests. Please use CLI interface to minify files (php index.php minify-all).")
                    ->appendTo($this);
                return;
            }
			$this->content("<h1>Select what to minify</h1>");
			$form = $this->content( new Form() );
			$form->AddHidden('submitter','1');
			$form->AddCheckbox('skip_minify','Skip minify (only collect and combine)<br/>');
			$form->AddCheckbox('random_nc','Generate random name (instead of app version)<br/>');
			$form->AddSubmit('Go');
			return;
		}
		
		$this->content("<h1>Minify DONE</h1>");
        system_load_module('modules/cli.php');
        
        $task = \ScavixWDF\Tasks\MinifyTask::Make();
        
        $task->All([$GLOBALS['CONFIG']['system']['url_root'],$skip_minify?'n':false,$random_nc?'y':false]);
        
        $this->content("<pre>". var_export($task->Results,true)."</pre>");
	}
}