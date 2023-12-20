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

$js_cookie_error = '<div style="display: block !important; font-weight:bold; text-align: center;"><br/>ERR_JAVASCRIPT_AND_COOKIES_REQUIRED</div>';
$render_noscript_block = isset($render_noscript_block)?$render_noscript_block:ScavixWDF\Base\HtmlPage::$RENDER_NOSCRIPT;
$doctype = isset($doctype)?$doctype:ScavixWDF\Base\HtmlPage::$DOCTYPE;

echo '<?xml version="1.0" encoding="UTF-8" ?>'."\n$doctype\n";?>
<html xmlns="http://www.w3.org/1999/xhtml"<?=(isset($languagecode) ? ' lang="'.$languagecode.'"' : '')?>>
<head>
<?php
if( isset($polyfills) && count($polyfills)>0 )
    echo '<script src="https://polyfill.io/v3/polyfill.js?features='.implode(",",$polyfills).'"></script>';
?>
	<title><?=$title?></title>
<?php if(isset($favicon) && $favicon) { ?>
	<link rel="shortcut icon" href="<?=$favicon?>" />
<?php } ?>
	<?php foreach($meta as $m) echo $m; ?>
	<?php if(isset($inlineheaderpre)) echo $inlineheaderpre; ?>
	<?php foreach($css as $c) echo $c; ?>
	<?php foreach($js as $j) echo $j; ?>
	<?php if(isset($inlineheader)) echo $inlineheader; ?>
<script type='text/javascript'>
$(function(){
<?php if( $render_noscript_block ): ?>
	if( !navigator.cookieEnabled && window.self == window.top )
		return $('body').empty().append('<?=$js_cookie_error?>');
<?php endif; ?>
<?php if( count($docready) > 0 ): ?>
	wdf.ready.add(function()
	{
	<?=implode("\n",$docready);?>
<?php if( isset($_SESSION['wdf_translator_mode']) && $_SESSION['wdf_translator_mode'] && isset($GLOBALS['translation']['strings']) ):
    $translations = cache_get('wdf_translator_strings', false);
    if(!$translations)
    {
        $translations = array_combine(array_map(function($k){return $k."[NT]"; },array_keys($GLOBALS['translation']['strings'])),array_map(function($v){return (isset($GLOBALS['translation']['strings'][$v]) ? $v.'[NT]' : __translate($v)); },array_values($GLOBALS['translation']['strings'])));
        cache_set('wdf_translator_strings', json_encode($translations));
    }
    ?>
    wdf.translations = <?=$translations?>;
    wdf.translator_hint = $('<div/>').addClass('wdf_translator_hint').appendTo('body').hide();
    $(document).on('mouseover','*',function(e)
    {
        var texts = [];
        $(e.target).find(":not(iframe)").addBack().contents().filter(function()
        {
            return this.nodeType == 3;
        }).each(function(){ texts.push($(this).text()); });
        texts.push($(e.target).text());
        var buf = [], matches={};
        var testlen = 20;
        for(var t=0; t<texts.length; t++)
        {
            var txt = texts[t], part = txt.substr(0,testlen);
            for(var i in wdf.translations)
            {
                if( matches[i] )
                    continue;
                var test = wdf.translations[i];
                if( test==txt || (txt.length > testlen && test.substr(0,part.length)==part) )
                {
                    matches[i] = wdf.translations[i];
                    buf.push("<div>"+i+"</div>");
                    break;
                }
            }
        }
        if(buf.length > 0)
            wdf.translator_hint.html(buf.join("")).position({my:'left top', at:'left bottom',of:$(e.target)}).show();
        else
            wdf.translator_hint.hide();
    });
<?php endif;?>
	});
<?php endif; ?>
	<?=$wdf_init?>
});
	<?=implode((isDev() ? "\n" : ""),$plaindocready)?>
</script>
</head>
<body<?=(isset($isrtl)?$isrtl:"")?><?=isset($bodyClass)?" class='$bodyClass'":""?>>
<?php if( $render_noscript_block ): ?>
<noscript>
	<style type="text/css">
		body>*:not(noscript) { display:none !important; }
	</style>
	<?=$js_cookie_error?>
</noscript>
<?php endif; ?>
<?php
if( isset($sub_template_content) )
	echo $sub_template_content;
else
	foreach($content as $c) echo $c;
?>
</body>
</html>
