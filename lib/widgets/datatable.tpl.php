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
 
if( count($data) < 1 ) $header = array("dummy_for_colspan");
$align = isset($align)?$align:"left";
$float = (isset($float) ? "float:$float" : ($align=='left'||$align=='right'?"float:$align;":""));
$width = isset($width) ? " width=\"$width\"" : "";
$mouseover[0] = isset($hoverclass0) ? " onmouseover=\"this.className='$hoverclass0'\"" : "";
$mouseover[1] = isset($hoverclass1) ? " onmouseover=\"this.className='$hoverclass1'\"" : "";

$mouseout[0] = ( $mouseover[0] != "" )?" onmouseout=\"this.className='tr_0'\"":"";
$mouseout[1] = ( $mouseover[1] != "" )?" onmouseout=\"this.className='tr_1'\"":"";
?>
<? if( (isset($force_creation) && $force_creation) || !isset($GLOBALS['ajax_active']) ): ?>
<div id="<?=$id?>" align="<?=$align?>" style="<?$float?>">
<? endif; ?>
<div id="<?=$id?>_overlay" class="dt_overlay">
	<br/><br/><br/>
	<img src="<?=skinFile('loading.gif')?>"/>
</div>
<div>
<table class="datatable"<?=$width?>>
	<? if( isset($title) ): ?>
	<tr class="dt_headerrow">
		<td class="dt_header" colspan="<?=count($header)?>"><?=$title?></td>
		<? if(isset($infobuttontextid)) : ?>
		<td align=right><a href="javascript:alert('<?=$infobuttontextid?>')"><img src="<?=skinfile("info_button.png")?>" class=datatableinfobutton /></a></td>
		<? endif; ?>
	</tr>
	<? endif; ?>
<? if( count($data) > 0 ): // if there's data ?>
	<tr>
		<?
			$i = 0;
			foreach( $header as $h )
			{
				$a = isset($aligns[$i])?$aligns[$i]:"left";
				$i++;
				echo "<td class='dt_captionscol' align='$a'".($i == count($header) && isset($infobuttontextid) ? " colspan=2" : "").">$h</td>\n";
			}
		?>
	</tr>
	<?
	$i = 0; $cnt = 1;
	foreach( $data as $row )
	{
		$over = $mouseover[$i];
		$out  = $mouseout[$i];

		echo "<tr class='tr_$i'$over$out";
		if(isset($row["__callback_onclick"]))
			echo " onclick=\"".$row["__callback_onclick"]."\" style=\"cursor: pointer\"";
		echo ">\n";
		$j = 0;
		foreach( $row as $key=>$val )
		{
			if(substr($key, 0, 11) == "__callback_")
				continue;
			$a = isset($aligns[$j])?$aligns[$j]:"left";
			$j++;

			echo "\t<td".($j == count($header) && isset($infobuttontextid) ? " colspan=2" : "")." class='";
			if( $cnt == count($data) && isset($last_is_footer) && $last_is_footer)
				echo "dt_summary'".($a != "left" ? " align='$a'" : "").">$val</td>\n";
			else
				echo "dt_row$i'".($a != "left" ? " align='$a'" : "")." nowrap>$val</td>\n";
		}
		echo "</tr>\n";
		$i = $i==0?1:0;
		$cnt++;
	}
	foreach( $extrafooter as $row )
	{
		echo "<tr>\n";
		$j = 0;
		foreach( $row as $key=>$val )
		{
			$a = isset($aligns[$j])?$aligns[$j]:"left";
			$j++;
			echo "\t<td class='dt_summary' align='$a'".($j == count($header) && isset($infobuttontextid) ? " colspan=2" : "").">$val</td>\n";
		}
		echo "</tr>\n";
	}
	?>
	<? if( isset($pager) ): ?>
	<tr><td class="dt_summary" colspan="><?=(count($header)+(isset($infobuttontextid) ? 1 : 0))?>"><?=$pager?></td></tr>
	<? endif; ?>
<? else: // if no data ?>
	<tr><td><?=getString("TXT_NO_DATA_FOUND")?></td></tr>
<? endif; ?>
</table>
</div>
<? if( (isset($force_creation) && $force_creation) || !isset($GLOBALS['ajax_active']) ): ?>
</div>
<? endif; ?>
<script>
$(document).ready( function(){
	$('#<?=$id?>_overlay').hide();
	<? if( (isset($force_creation) && $force_creation) || !isset($GLOBALS['ajax_active']) ):?>
	//console.log('dt_init because not in ajax');
	dt_init('<?=$id?>');
	<? endif; ?>
});
</script>
