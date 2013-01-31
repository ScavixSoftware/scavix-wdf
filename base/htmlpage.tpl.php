<?php
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
 
echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n";?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?=$title?></title>
<? if(isset($favicon) && $favicon) { ?>
	<link rel="shortcut icon" href="<?=$favicon?>" />
<? } ?>
	<? foreach($meta as $m) echo $m; ?>
	<? foreach($css as $c) echo $c; ?>
	<? foreach($js as $j) echo $j; ?>
	<? if(isset($inlineCSS)) echo $inlineCSS; ?>
<? if( count($docready) > 0 || count($plaindocready) > 0 ): ?>
<script type='text/javascript'>
<? if( count($docready) > 0 ): ?>
$(document).ready( function()
{
	<?=implode("\n\t",$docready);?>
});
<? endif; ?>
<? if( count($plaindocready) > 0 ) echo implode("\n\t",$plaindocready)."\n"; ?>
</script>
<? endif; ?>
</head>
<body<? foreach($bodyEvents as $k=>$v) echo " $k='$v'";?><?=isset($isrtl)?"$isrtl":""?><?=isset($bodyClass)?" class='$bodyClass'":""?>>
<? if( $requireJs ): ?>
<noscript>
	<div id="noscript_message"><br/>ERR_NO_JAVASCRIPT</div>
	<style type="text/css">
		#spcontentcontainer {display: none;}
		#noscript_message {display: block !important; font-weight:bold; text-align: center;}
	</style>
</noscript>
<div id="spcontentcontainer">
<? endif; ?>
<?
if( isset($sub_template_content) )
	echo $sub_template_content;
else
	foreach($content as $c) echo $c;

if( isset($dialogs) && count($dialogs) > 0 )
{
	echo "<div id='GlobalDialogContainer' style='display:none'>";
	foreach($dialogs as $dlg) echo $dlg;
	echo "</div>";
}
?>
<? if( $requireJs ): ?>
</div>
<? endif; ?>
</body>
</html>
