<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
$track_prefix = isset($track_prefix)?$track_prefix:"";
$account_code = isset($account_code)?$account_code:"";
$js_varname = isset($js_varname)?$js_varname:"pageTracker";
$track_immediately = isset($track_immediately)?$track_immediately:true;
?>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try
{
	<?=$js_varname?> = _gat._getTracker("<?=$account_code?>");
	<?=$js_varname?>._initData();
	<?php if( $track_immediately ): ?>
	<?=$js_varname?>._trackPageview();
	<?php endif; ?>

	wdf.ready.add( function()
	{
		$('*[title]').each( function()
		{
			var code = ($(this).attr("title")||'').match(/ga_click_track\(([^\)]+)\)/);
			if( code && code[1] )
			{
				$(this).attr("title",
					($(this).attr("title")||'').replace(/ga_click_track\(([^\)]+)\)/,"")
				);
				$(this).click( function()
				{
					<?=$js_varname?>._trackPageview('<?=$track_prefix?>' + code[1]);
				});
			}
		});
	});
}
catch(e){ }
</script>