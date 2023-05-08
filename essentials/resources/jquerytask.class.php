<?php

class jQueryTask extends \ScavixWDF\Tasks\Task
{
    function Run($args)
    {
        
    }

    /**
     * Updates the standard jQuery UI assets that are delivered with the Scavix-WDF.
     * 
     * @param mixed $args Optional arguments, version=<v> may override the standard version 1.13.2
     * @return void
     */
    function UpdateUI($args)
    {
        $args['target'] = __DIR__ . '/../../res';
        $args['url'] = '?bgShadowXPos=&bgOverlayXPos=&bgErrorXPos=&bgHighlightXPos=&bgContentXPos=&bgHeaderXPos=&bgActiveXPos=&bgHoverXPos=&bgDefaultXPos=&bgShadowYPos=&bgOverlayYPos=&bgErrorYPos=&bgHighlightYPos=&bgContentYPos=&bgHeaderYPos=&bgActiveYPos=&bgHoverYPos=&bgDefaultYPos=&bgShadowRepeat=&bgOverlayRepeat=&bgErrorRepeat=&bgHighlightRepeat=&bgContentRepeat=&bgHeaderRepeat=&bgActiveRepeat=&bgHoverRepeat=&bgDefaultRepeat=&iconsHover=url(%22images%2Fui-icons_555555_256x240.png%22)&iconsHighlight=url(%22images%2Fui-icons_777620_256x240.png%22)&iconsHeader=url(%22images%2Fui-icons_444444_256x240.png%22)&iconsError=url(%22images%2Fui-icons_cc0000_256x240.png%22)&iconsDefault=url(%22images%2Fui-icons_777777_256x240.png%22)&iconsContent=url(%22images%2Fui-icons_444444_256x240.png%22)&iconsActive=url(%22images%2Fui-icons_ffffff_256x240.png%22)&bgImgUrlShadow=&bgImgUrlOverlay=&bgImgUrlHover=&bgImgUrlHighlight=&bgImgUrlHeader=&bgImgUrlError=&bgImgUrlDefault=&bgImgUrlContent=&bgImgUrlActive=&opacityFilterShadow=Alpha(Opacity%3D30)&opacityFilterOverlay=Alpha(Opacity%3D30)&opacityShadowPerc=30&opacityOverlayPerc=30&iconColorHover=%23555555&iconColorHighlight=%23777620&iconColorHeader=%23444444&iconColorError=%23cc0000&iconColorDefault=%23777777&iconColorContent=%23444444&iconColorActive=%23ffffff&bgImgOpacityShadow=0&bgImgOpacityOverlay=0&bgImgOpacityError=95&bgImgOpacityHighlight=55&bgImgOpacityContent=75&bgImgOpacityHeader=75&bgImgOpacityActive=65&bgImgOpacityHover=75&bgImgOpacityDefault=75&bgTextureShadow=flat&bgTextureOverlay=flat&bgTextureError=flat&bgTextureHighlight=flat&bgTextureContent=flat&bgTextureHeader=flat&bgTextureActive=flat&bgTextureHover=flat&bgTextureDefault=flat&cornerRadius=3px&fwDefault=normal&ffDefault=Arial%2CHelvetica%2Csans-serif&fsDefault=1em&cornerRadiusShadow=8px&thicknessShadow=5px&offsetLeftShadow=0px&offsetTopShadow=0px&opacityShadow=.3&bgColorShadow=%23666666&opacityOverlay=.3&bgColorOverlay=%23aaaaaa&fcError=%235f3f3f&borderColorError=%23f1a899&bgColorError=%23fddfdf&fcHighlight=%23777620&borderColorHighlight=%23dad55e&bgColorHighlight=%23fffa90&fcContent=%23333333&borderColorContent=%23dddddd&bgColorContent=%23ffffff&fcHeader=%23333333&borderColorHeader=%23dddddd&bgColorHeader=%23e9e9e9&fcActive=%23ffffff&borderColorActive=%23003eff&bgColorActive=%23007fff&fcHover=%232b2b2b&borderColorHover=%23cccccc&bgColorHover=%23ededed&fcDefault=%23454545&borderColorDefault=%23c5c5c5&bgColorDefault=%23f6f6f6';
        $this->GrabUI($args);
    }
   
    /**
     * Downloads a fully working and consistent copy of jQuery UI to a given folder.
     * 
     * @param mixed $args Arguments are: target=<target folder, subfolder 'jquery-ui' will becreted> [version=<jquery-ui-version>] [<varname>=<varvalue>|url=<themeroller-url>]
     * @return void
     */
    function GrabUI($args)
    {
        $target = ifavail($args, 'target');
        $target = $target?realpath($target.''):'';
        if( !$target || !is_dir($target) || !file_exists($target) )
        {
            log_error("Syntax: jquery-grabui target=<resource-folder> [version=<jq-version, default=1.13.2>] [<varname>=<value>|url=<url from downloaded themeroller file>]");
            return;
        }
        $version = ifavail($args, 'version')?:'1.13.2';
        if( $url = ifavail($args,'url') )
        {
            $url = parse_url($url);
            if( count($url)==1 )
                parse_str(array_values($url)[0], $query);
            else
                parse_str($url['query'], $query);

            $args = array_filter($query);
            log_debug("Using overrides from URL, all other overrides are ignored");
        }
        if( avail($args,'zThemeParams') )
        {
            if (PHP_OS_FAMILY == "Linux")
            {
                $decoded = shell_exec("echo " . ifavail($args, 'zThemeParams') . " | xxd -r -p - | lzma -dc 2>/dev/null");
                if ($decoded && ($args = @json_decode($decoded, true)))
                    log_debug("Using overrides from zThemeParams, all other overrides are ignored", $args);
                else
                    log_debug("Could not decode given zThemeParams (make sure 'xxd' and 'lmza' are installed)");
            }
            else
                log_debug("zThemeParams can only be decoded on Linux platform");
        }

        foreach( $args as $k=>$v )
        {
            if (preg_match('/^[0-9a-f]{6}$/i', $v) || preg_match('/^[0-9a-f]{8}$/i', $v))
                $args[$k] = "#$v";
        }
        $this->processTextures($args);

        $targetPath = "$target/jquery-ui";
        @mkdir($targetPath);
        @mkdir("$targetPath/images");
        $vars = [];
        $props = [];
        $less = preg_replace_callback('/\s([^\s]*)\/\*{([^\*]+)}\*\//', function ($m) use ($args, &$vars, &$props, $targetPath)
        {
            $rawName = $m[2];
            $name = "@jqui-{$rawName}";
            $val = $m[1] ?: "''";
            if (!isset($vars[$name]))
            {
                log_debug("Generating variable $name: $val;");
                if( avail($args, $rawName) )
                {
                    $val = ifavail($args, $rawName);
                    log_debug("-> value overwritten to $val");
                }

                $vars[$name] = "$name: $val;";
                $props[$name] = str_replace("@", "--", $name) . ": $name;";

                if( preg_match('/images\/ui-icons_([0-9a-f]{6})_.*\.\w+/i',$val,$match) )
                {
                    $remote = $match[0];
                    $colName = str_replace('icons', 'iconColor', $rawName);
                    if( avail($args, $colName) )
                    {
                        $hex = strtolower(trim(ifavail($args, $colName), ' #'));
                        $remote = str_replace("_{$match[1]}_", "_{$hex}_", $remote);
                        log_debug("-> icon color overwritten to $hex");
                    }

                    log_debug("Loading image {$remote}...");
                    $fn = "$targetPath/images/{$rawName}.png";
                    $img = file_get_contents("https://download.jqueryui.com/themeroller/{$remote}");
                    
                    // if( !file_exists($fn) || filesize($fn) != strlen($img) )
                    // {
                    //     log_debug("...written to $fn");
                        file_put_contents("$fn", $img);
                    // }
                    // else
                    //     log_debug("...already up to date");

                    $val = str_replace("$match[0]", "images/{$rawName}.png", $val);
                    $vars[$name] = "$name: $val;";
                }
                elseif( preg_match('/images\/ui-bg_.*\.\w+/i',$val,$match) )
                {
                    $remote = $match[0];

                    log_debug("Loading texture {$remote}...");
                    $fn = "$targetPath/images/{$rawName}.png";
                    $img = file_get_contents("https://download.jqueryui.com/themeroller/{$remote}");
                    
                    // if( !file_exists($fn) || filesize($fn) != strlen($img) )
                    // {
                        file_put_contents("$fn", $img);
                    //     log_debug("...written to $fn");
                    // }
                    // else
                    //     log_debug("...already up to date");

                    $val = str_replace("$match[0]", "images/{$rawName}.png", $val);
                    $vars[$name] = "$name: $val;";
                }
            }
            return " ".str_replace("@", "var(--", $name) . ")";
        }, $this->loadFile('all.css',$version));

        $header = implode("\n", $vars) . "\n:root {\n\t" . implode("\n\t", $props) . "\n}";
        $less = "{$header}\n{$less}";
        $less = str_replace(
            ['@VERSION','"images/'], 
            [$version,'"resFile/jquery-ui/images/'], 
            $less);

        log_debug("Writing less file");
        file_put_contents("$targetPath/jquery-ui.less", $less);
    }

    private function loadFile($filename,$version='main')
    {
        $url = "https://raw.githubusercontent.com/jquery/jquery-ui/$version/themes/base";
        log_debug("Loading $version/$filename");
        $content = file_get_contents("$url/$filename");
        if (!ends_iwith($filename, ".css"))
            return $content;
        return preg_replace_callback('/@import\s+(url\()*"(.+)"[\)]*;/', function ($m)use($version)
        {
            return $this->loadFile($m[2],$version);
        }, $content);
    }

    private function processTextures(&$args)
    {
        static $sizes =
        [
            '3D-boxes' => '12x10',
            'carbon-fiber' => '8x9',
            'diagonal-maze' => '10x10',
            'diagonals-medium' => '40x40',
            'diagonals-small' => '40x40',
            'diagonals-thick' => '40x40',
            'diamond' => '10x8',
            'diamond-ripple' => '22x22',
            'dots-medium' => '4x4',
            'dots-small' => '2x2',
            'fine-grain' => '60x60',
            'flat' => '40x100',
            'glass' => '1x400',
            'gloss-wave' => '500x100',
            'glow-ball' => '600x600',
            'hexagon' => '12x10',
            'highlight-hard' => '1x100',
            'highlight-soft' => '1x100',
            'inset-hard' => '1x100',
            'inset-soft' => '1x100',
            'layered-circles' => '13x13',
            'loop' => '21x21',
            'spotlight' => '600x600',
            'white-lines' => '40x100',
        ];
        foreach( $args as $k=>$texture )
        {
            if (!stripos($k, 'texture'))
                continue;

            $texture = str_replace("_", "-", $texture);
            $name = array_last(explode("exture", $k));
            $op = ifavail($args, "bgImgOpacity{$name}") ?: '10';
            $col = trim(ifavail($args, "bgColor{$name}") ?: '444444', ' #');
            $size = ifavail($sizes, $texture) ?: '10x8';

            $args["bgImgUrl{$name}"] = "url(\"images/ui-bg_{$texture}_{$op}_{$col}_{$size}.png\")";
            $args["bg{$name}XPos"] = "50%";
            $args["bg{$name}YPos"] = "50%";
            $args["bg{$name}Repeat"] = "repeat";
            log_debug("Generated Texture '$texture' data for state '$name'");
        }
    }
}
