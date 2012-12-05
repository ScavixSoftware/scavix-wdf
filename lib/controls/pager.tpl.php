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
 
 if(count($anchors) > 0) { ?>
<span align="center" class="pager"><?=$prefix?>
<?
foreach( $anchors as $a )
	echo "&nbsp;".$a;
?>
<?=$suffix?></span>
<?
}

 /*
<? if( isset($ajax) && $ajax ): ?>
<script>
$(document).ready( function() {
/*
	$('#<?=$ajax_target?> span.pager a').click( function() {

		//try{
		dt_load('<?=$ajax_target?>',$(this).attr('href') + '&load=<?=$ajax_target?>&event=gotopage');
		//}catch(ex){ console.log(ex); }
		return false;
	});* /
});
</script>
<? endif;?>
*/ ?>