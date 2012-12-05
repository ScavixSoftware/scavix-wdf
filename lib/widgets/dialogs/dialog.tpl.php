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
 
$modal = isset($modal)?$modal:true;
//$modal = true;
$showcallback = isset($showcallback) ? $showcallback : "";
?>
<table id="<?=$id?>" class="<? echo implode(" ",$classes); ?>" cellpadding="0" cellspacing="0">
	<? if( isset($title) && $title != "" ): ?>
	<tr>
		<td class="dlg_title_left"><?=$trans?></td>
		<td class="dlg_title_mid"><?=$title?></td>
		<td class="dlg_title_right"><?=$trans?></td>
	</tr>
	<? else: ?>
	<tr>
		<td class="dlg_top_left"><?=$trans?></td>
		<td class="dlg_top_mid"><?=$trans?></td>
		<td class="dlg_top_right"><?=$trans?></td>
	</tr>
	<? endif; ?>
	<tr>
		<td class="dlg_content_left"><?=$trans?></td>
		<td class="dlg_content_mid" id="<?=$id?>_content"><? foreach($content as $c) echo $c; ?></td>
		<td class="dlg_content_right"><?=$trans?></td>
	</tr>
	<? if( count($buttons) > 0 ): ?>
	<tr>
		<td class="dlg_content_left"><?=$trans?></td>
		<td class="dlg_content_mid"><? foreach($buttons as $but) echo $but; ?></td>
		<td class="dlg_content_right"><?=$trans?></td>
	</tr>
	<? endif; ?>
	<tr>
		<td class="dlg_end_left"><?=$trans?></td>
		<td class="dlg_end_mid"><?=$trans?></td>
		<td class="dlg_end_right"><?=$trans?></td>
	</tr>
</table>
<? if( !isset($no_js_code) || $no_js_code == false ): ?>
<script>
	$(document).ready(function() {

		$('#<?=$id?>').jqm({
			trigger: '',
			overlay: 100,
			overlayClass: 'whiteOverlay',
			toTop: true,
            modal: <?=$modal?'true':'false'?>,
			onShow: function(hash)
			{
				var mtop = $(window).scrollTop()+((hash.o.height()-hash.w.height()) / 4);
				hash.w.fadeIn('fast').css({
					marginLeft: -1 * (hash.w.width() / 2),
					top: mtop,
					position: 'absolute'
				});
				dialog_last_opened = hash.w;
                <?=$showcallback?>
			},
			onHide: function(hash)
			{
				hash.w.fadeOut('slow');
				hash.o.fadeOut('fast',function()
				{
					hash.o.remove();
				}); 
			}
		});

<? foreach($trigger as $t): ?>
		$('#<?=$id?>').jqmAddTrigger('<?=$t?>');
<? endforeach; ?>
<? foreach($closer as $c): ?>
		$('#<?=$id?>').jqmAddClose('<?=$c?>');
<? endforeach; ?>
	});
</script>
<? endif; ?>