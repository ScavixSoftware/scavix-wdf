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
<form id="<?=$id?>" action="<?=$action?>" method="<?=$method?>" enctype="multipart/form-data">
<? if( count($controls) > 0 ): ?>
<table class="controlgrid" cellspacing="0" cellpadding="0">
<?
$cnt = count($controls);
for($i=0; $i<$cnt; $i++ ): ?>
	<tr>
		<? if( $controls[$i] == "" ): ?>
		<td class='controlgrid_label' colspan="3"><?=$labels[$i]?></td>
		<? else: ?>
		<td class='controlgrid_label'><?=$labels[$i]?></td>
		<td class='controlgrid_content'>
			<?
			if( is_array($controls[$i]) )
				foreach( $controls[$i] as $c ) echo $c;
			else
				echo $controls[$i];
			?>
		</td>
		<td class='controlgrid_hint'><?=$hints[$i]?></td>
		<? endif; ?>
	</tr>
<? endfor; ?>
</table>
<? endif; ?>
</form>
<script>
$(document).ready( function(){

	$('a').filter(':not(.<?=$id?>_link,.<?=$id?> a)').click( function(){

		var prms = {
			load: 'wizard',
			event: 'HasBeenChanged',
			href: $(this).attr('href')
		}
		jQuery.get("?",prms,function(d){ eval(d); });
		return false;
	});

});
</script>