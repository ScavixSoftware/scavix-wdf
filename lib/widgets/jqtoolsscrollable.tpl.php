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
<!-- "previous page" action -->
<a class="prev browse left"></a>

<!-- root element for scrollable -->
<div class="scrollable" style="width:<?=$width?>px; height:<?=$height?>px;">

	<!-- root element for the items -->
	<div class="items">
		<?foreach($itemgroups as $ig):?>
		<div style="width:<?=$width?>px;">
			<?foreach($ig as $item):?>
			<?=$item?>
			<?endforeach;?>
		</div>
		<?endforeach;?>
   </div>

</div>

<!-- "next page" action -->
<a class="next browse right"></a>

<script>
	// custom easing called "jqtoolseasing"
	$.easing.jqtoolseasing = function (x, t, b, c, d) {
		var s = 1.70158;
		if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
		return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
	}

	$(function()
	{
		$(".scrollable").scrollable(<?=$options?>);
	});
</script>

