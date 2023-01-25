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
    var log = function (msg)
        {
            msg = util.inspect(msg);
            var dt = new Date().toISOString().slice(0,19).replace(/T/," ");
            fs.appendFile('{{logfile}}','['+dt+'] [DEBUG] (PUP)\t'+msg+'\n',()=>{});
        };
    const browser = await puppeteer.launch({args: ['--no-sandbox', '--disable-setuid-sandbox']});
    const page = await browser.newPage();
    const ua = await browser.userAgent();
    await page.setUserAgent(ua+" WDF/Puppeteer");
    page.on('console', log).on('error', log).on('pageerror', log);
    
    await page.goto('{{url}}', {waitUntil: 'networkidle0'}).catch(log);
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
        if( window.wdf && window.wdf.initPrinting )
            window.wdf.initPrinting(29.7,parseInt('{{dpi}}'));
    });

    await page.setViewport({width: 1920, height: 1080, deviceScaleFactor: 2})
    await page.pdf({path: '{{fn}}', format: 'A4', printBackground: true, scale: 0.9}).catch(log);
    await browser.close();
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
        return stripos(ifavail($_SERVER,'HTTP_USER_AGENT')?:'',"WDF/Puppeteer") > -1;
    }
    
    /**
     * Actual PDF creation.
     * 
     * This may be used sync from PHP code or async via Task.
     * @param string $url The URL to render as PDF
     * @return string|false Returns the PDF filename or false on error
     */
    static function Url2Pdf($url)
    {
        $um = umask(0);
        $node_root = self::detectPuppeteer();
        
		$fn = tempnam(system_app_temp_dir(),'PdfPrintTask_Url2Pdf_');
        $cfg = false&&avail($GLOBALS,'CONFIG','system','logging','human_readable')
            ?$GLOBALS['CONFIG']['system']['logging']['human_readable']
            :['path'=>'','filename_pattern'=>ini_get('error_log')];
        
		// prepare JS script
		$script = str_replace
        (
            [
                '{{dpi}}',
                '{{fn}}',
                '{{url}}',
                '{{logfile}}',
                '{{docroot}}',
                '{{isdev}}'
            ], 
            [
                144,
                str_replace("\\", "/", $fn),
                $url,
                str_replace("\\","/",$cfg['path'].$cfg['filename_pattern']),
                $node_root,
                (isDev()?'true':'false')
            ],
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
		$res = trim(shell_exec($puppeteerjs));
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
