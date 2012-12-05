<?
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
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
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
?>
<div id='<?=$id?>' class='clickmenu'>
<?=$label?> <img src='<?=skinFile('layoutmenu/arrow.gif')?>'/>
<ul>
<? foreach( $items as $item ) echo $item; ?>
</ul>
</div>
<script>
function <?=$id?>_perform_click(jq_obj)
{
<? if(is_array($cases)) { ?>
	switch($.trim(jq_obj.text()))
	{
		<? foreach( $cases as $item ) echo $item; ?>
	}
<? } ?>
}

$(document).ready( function() {
		ClickMenu_Init('#<?=$id?>',<?=$id?>_perform_click);
	});
</script>