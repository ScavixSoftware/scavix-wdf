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
 
 if( $data && $data->_saved ): ?>
<table class="dataset">
	<? if( isset($title) ): ?>
	<tr>
		<td class="ds_header" colspan="2"><?=$title?></td>
	</tr>
	<? endif; ?>

<?
	$i = 0;
	foreach( $data->GetAttributeNames() as $attr )
	{
		if( $data->$attr == "" )
			continue;

		echo "<tr><td class='ds_label$i'>$attr</td>\n";
		echo "<td class='ds_content$i'>".$data->$attr."</td></tr>\n";
		$i = $i==0?1:0;
	}
?>
</table>
<? if( isset($external_loading) && $external_loading ): ?>
<script>
$(document).ready( function(){
	$('#<?=$external_loading?>').fadeOut('fast');
});
</script>
<? endif; ?>
<? endif; ?>