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
 
 if( !isset($CHART_SCRIPT_WRITTEN) ): $GLOBALS['CHART_SCRIPT_WRITTEN'] = true; ?>
<? endif; ?>
<div id="dv<?=$chartid?>" align="center" style="display:block;width:<?=$this->Width?>px;float:<?=$this->Align?>;">
	<div style="display:block; border:1px solid #C0C0C0; width:<?=$this->Width?>px; height:<?=$this->Height?>px;">
		<p style="text-align:center;">The chart is currently loading. Please stand by...</p>
		<p style="text-align:center;">FusionCharts needs Adobe Flash Player to run. If you're unable to see the chart here, it means that your browser does not seem to have the Flash Player Installed. You can downloaded it <a href="http://www.adobe.com/products/flashplayer/" target="_blank"><u>here</u></a> for free.</p>
	</div>
</div>
<script type="text/javascript">
   var <?=$chartid?> = new FusionCharts("<?=$chartswffile?>", "<?=$chartid?>", "<?=$width?>", "<?=$height?>", "<?=$debug?>", "0");
<? if( !$performLazyLoad ): ?>
   <?=$chartid?>.setDataXML("<?=$chartxml?>");
   <?=$chartid?>.setTransparent(true);
   <?=$chartid?>.render("dv<?=$chartid?>");
<? endif; ?>
</script>