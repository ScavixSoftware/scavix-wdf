<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2021 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2021 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

/**
 * Wraps PDF printing using puppeteer
 *
 * @see https://github.com/puppeteer/puppeteer
 */
class PdfPrintTask extends Task
{
    const puppeteer_script = <<<'EOPS'
const fs = require('fs'), util = require('util'), puppeteer = require('{{docroot}}/node_modules/puppeteer');
async function wdf_print()
{
    var resolveHandle = (handle) =>
    {
        return handle.executionContext().evaluate((d) =>
        {
            if( typeof(d) == "object" )
                return JSON.stringify(d);
            return d;
        },handle);
    };
    var log = async function (input)
    {
        var msg = (input instanceof Error)?['['+input.name+']',input.message,input.cause]:[];
        if( msg.length == 0 && typeof(input.text)=='function' )
        {
            var txt = input.text();
            msg = await Promise.all(input.args().map(arg => resolveHandle(arg)));
            if( msg.length == 0 )
                msg.unshift(txt);
            try{ msg.push(JSON.stringify(input.location())); }catch(nil){}
            msg.unshift('[console]');
        }
        if( msg.length == 0 )
            msg = ['[inspect]',util.inspect(input),"(URL was '{{url}}')"];

        var dt = new Date().toISOString().slice(0,19).replace(/T/," ");
        m = msg.join('\t');
        if((m == '') || (m == '{}') || (m == 'ConsoleMessage {}))
            return;
        fs.appendFile('{{logfile}}','['+dt+'] [DEBUG] (PUP)\t' + m + '\n',()=>{});
    };
    const browser = await puppeteer.launch({headless: 'new', args: ['--no-sandbox', '--disable-setuid-sandbox']});
    try
    {
        const page = await browser.newPage();
        const ua = await browser.userAgent();
        await page.setUserAgent((ua+" WDF/Puppeteer {{userAgentSuffix}}").trim());
        page.on('console', log).on('error', log).on('pageerror', log);

        await page.goto('{{url}}', {waitUntil: 'networkidle0', timeout: {{timeout}}}).catch(log);
        await page.evaluate(async () =>
        {
            document.body.scrollIntoView(false);
            await Promise.all(Array.from(document.getElementsByTagName('img'), image =>
            {
                if (image.complete) return;
                return new Promise((resolve, reject) =>
                {
                    image.addEventListener('load', resolve);
                    image.addEventListener('error', reject);
                });
            }));
            if( window.exists('{{pageInitFunction}}') )
                window.{{pageInitFunction}}(29.7,parseInt('{{dpi}}'));
        });

        await page.setViewport({width: 1920, height: 1080, deviceScaleFactor: 2})
        await page.pdf({path: '{{fn}}', format: 'A4', printBackground: true, scale: 0.9}).catch(log);
    }
    finally
    {
        await browser.close();
    }
};
wdf_print();
EOPS;

    /**
     * @internal Checks if puppeteer is installed correctly
     */
    static function detectPuppeteer()
    {
        if( !preg_match('/^v\d+\.\d+\.\d+/i',shell_exec("node -v")) )
            \ScavixWDF\WdfException::Raise("Node not found!");

        $dir = str_replace("\\", "/", getcwd());
        while( !file_exists("{$dir}/node_modules/puppeteer") )
        {
            $o = $dir;
            $dir = dirname($dir);
            if( $o == $dir )
                break;
        }
        if( file_exists("{$dir}/node_modules/puppeteer") )
            return $dir;

        if( preg_match('/^\d+\.\d+\.\d+/i',shell_exec("npm -v")) )
        {
            $dir = shell_exec("npm roo --global");
            if( file_exists("{$dir}/node_modules/puppeteer") )
                return $dir;
        }
        \ScavixWDF\WdfException::Raise("Puppeteer not found!");
    }

    /**
     * Runs PDF creation.
     *
     * @param array $args Named args: url=<url> pdf=<filename>
     * @return void
     */
    function Run($args)
    {
        $url = ifavail($args,'url');
        $pdf = ifavail($args,'pdf');
        if( !$url || !$pdf )
        {
            log_info("Syntax: pdfprint url=<url> pdf=<filename>");
            return;
        }

        $fn = self::Url2Pdf($url);
        if( !file_exists($fn) )
        {
            log_error("Unable to create PDF");
            return;
        }

        if( !rename($fn,$pdf) )
        {
            unlink($fn);
            log_error("Unable to create file '$pdf'");
            return;
        }
    }

    /**
     * Helper method to detect active puppeteer calls.
     *
     * May be used from within page renderer to detect if the call is from this task.
     * @return bool true or false
     */
    public static function IsPrinterCall()
    {
        return stripos(ifavail($_SERVER,'HTTP_USER_AGENT')?:'',"WDF/Puppeteer") !== false;
    }

    /**
     * Actual PDF creation.
     *
     * This may be used sync from PHP code or async via Task.
     * @param string $url The URL to render as PDF
     * @param array $options Some parameters are available to be specified as options. See source code for details.
     * @return string|false Returns the PDF filename or false on error
     */
    static function Url2Pdf($url, $options=[])
    {
        $um = umask(0);
        $node_root = self::detectPuppeteer();

		$fn = tempnam(system_app_temp_dir(),'PdfPrintTask_Url2Pdf_');
        $cfg = false&&avail($GLOBALS,'CONFIG','system','logging','human_readable')
            ?$GLOBALS['CONFIG']['system']['logging']['human_readable']
            :['path'=>'','filename_pattern'=>ini_get('error_log')];

        $options = array_merge([
            'dpi' => 144,
            'fn' => str_replace("\\", "/", $fn),
            'url' => $url,
            'logfile' => str_replace("\\", "/", $cfg['path'] . $cfg['filename_pattern']),
            'docroot' => $node_root,
            'isdev' => (isDev() ? 'true' : 'false'),
            'timeout' => 30000,
            'userAgentSuffix' => '',
            'pageInitFunction' => 'wdf.initPrinting',
        ], $options);
        $keys = array_map(function ($k){ return '{{' . $k . '}}'; }, array_keys($options));
        $values = array_values($options);

		// prepare JS script
        $script = str_replace($keys, $values,
            // for debugging/development put constant text into puppeteer.js next to this file
            //file_get_contents(__DIR__."/puppeteer.js")
            self::puppeteer_script
        );

		file_put_contents("$fn.js", $script);
        chmod("$fn.js",0777);

		// go puppeteer, go puppeteer, ...
        $puppeteerjs = "node $fn.js 2>&1";
        if(isDev())
            log_debug("Puppeteer CmdLine: $puppeteerjs",$script);
		$res = trim("".shell_exec($puppeteerjs));
        if( $res )
            log_debug("Puppeteer result:\n$res");
        umask($um);

        if( file_exists("$fn.js") && !$res )
            unlink("$fn.js");

        if( file_exists($fn) )
        {
            if( rename($fn,"$fn.pdf") )
                return "$fn.pdf";
            return $fn;
        }
        return false;
    }
}
