<?php
/**
 * Scavix Web Development Framework
 *
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

/**
 * Task to perform webrequests.
 */
class WebRequest extends Task
{
    function __construct(WdfTaskModel $model=null)
    {
        parent::__construct($model);
    }

    /**
     * Perform a GET request.
     * 
     * @param string $url URL to get from
     * @param array $header Headers to send
     * @return string The response as text
     */
    public static function Get($url,$header=[])
    {
        return downloadData($url, false, $header, false, 30);
    }

    /**
     * Perform a POST request.
     * 
     * @param string $url URL to get from
     * @param array $data Data to send
     * @param array $header Headers to send
     * @return string The response as text
     */
    public static function Post($url,$data=[],$header=[])
    {
        return downloadData($url, $data, $header, false, 30);
    }

    /**
     * Triggers a web request to be run async.
     * 
     * @param string $url URL to get from
     * @param array $data Data to send
     * @param array $header Headers to send
     * @return void
     */
    public static function Trigger($url,$data=false,$header=[])
    {
        WdfTaskModel::Create(WebRequest::class)
            ->SetArgs(compact('url','data','header'))
            ->Go();
    }

    /**
     * @override
     */
    function Run($args)
    {
        if( PHP_SAPI == 'cli' )
        {
            list($url) = $this->mapCliArgs($args, false, 1);
            if( !$url )
            {
                log_warn("Syntax: webrequest <url>");
                return;
            }
            $issue = "CLI";
            $data = false;
            $header = false;
        }
        else
        {
            $issue = isset($args['issue'])?$args['issue']:'NoIssue';
            $url = $args['url'];
            $data = isset($args['data'])?$args['data']:false;
            $header = isset($args['header'])?$args['header']:[];
        }
        $response_header = [];
        $response = downloadData($url, $data, $header, false, 30, $response_header);

        if( PHP_SAPI == 'cli' )
            echo $response;
        else
            log_info("WebRequest $issue",$url,compact('data','header','response_header','response'));
    }
}