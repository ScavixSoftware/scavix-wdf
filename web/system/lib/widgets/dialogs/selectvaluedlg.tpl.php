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
<div id="svg_values" style="height: 300px; overflow: auto;"></div>
<script>

$(document).ready( function(){ SA_InSelect=false; });

function SelectValues(title,choices)
{
	if( !title )
	{
		title = "<?=trim(getString('TITLE_FILTER_CHOICE'))?>";
	}
	$('#<?=$dlgid?> td.dlg_title_mid').html(title);

	$('#svg_values').empty();

	for(var i=0; i<choices.length; i++)
	{
		var js = "SA_InSelect=false;";
		for(var j=0; j<choices[i].actions.length; j++)
			js += choices[i].actions[j];
		js += "$('#<?=$dlgid?>').jqmHide();";

		$('#svg_values').append(
			"<a id=\"select_link_"+ i +"\" href=\"#\" onclick=\""+ js +"\">"+ choices[i].label +"</a><br/>"
		);
	}

	$('#<?=$dlgid?>').jqm({
		trigger: false,
		overlay: 60,
		overlayClass: 'whiteOverlay',
		toTop: true,
		onHide: function(hash){
			SA_InSelect=false;
			hash.w.hide();
			hash.o.remove();
		}
	});
	$("#<?=$dlgid?>").jqmShow();
}
</script>