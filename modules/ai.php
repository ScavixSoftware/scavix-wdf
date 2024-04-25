<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2024 Scavix Software GmbH & Co. KG
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
 * @copyright since 2024 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * Initializes the AI module
 * @return void
 */
function ai_init()
{
    global $CONFIG;

    // classpath_add(__DIR__."/oauth");

    switch (ifavail($CONFIG['ai'], 'service'))
    {
        case 'google':
            require_once(__REPOROOT__.'/vendor/autoload.php'); // most likely already loaded in index.php!

            if( !class_exists("\\Google\\Cloud\\AIPlatform\\V1\\Client\\PredictionServiceClient") )
                throw new Exception("Missing Google AI client, see https://github.com/googleapis/google-cloud-php-ai-platform");

            $gconfig = false;
            if (($gconfigfile = ifavail($CONFIG['ai']['google'], 'configfile')) && file_exists($gconfigfile))
            {
                // $gconfigfile = realpath(__FILES__.'/google-a6db6eda054d.json');
                $gconfig     = json_decode(file_get_contents($gconfigfile), true);
                putenv('GOOGLE_APPLICATION_CREDENTIALS='.$gconfigfile);
            }

            if(!$gconfig && ($gconfigfile = getenv('GOOGLE_APPLICATION_CREDENTIALS')) && file_exists($gconfigfile))
                $gconfig = json_decode(file_get_contents($gconfigfile), true);

            if(!$gconfig)
                throw new Exception('Google AI module not configured');

            $CONFIG['ai']['handler'] = new WdfGoogleAIWrapper($gconfig);
            break;

        default:
            throw new Exception('AI module not configured');
    }
}

function ai_predict($prompt, $options = [], $cache = false)
{
    global $CONFIG;

    if(!avail($CONFIG['ai'], 'handler'))
        throw new Exception('AI module not configured');

    return $CONFIG['ai']['handler']->predict($prompt, $options, $cache);
}

/**
 * @internal Wrapper class for Google AI
 * @link https://cloud.google.com/php/docs/reference/cloud-ai-platform/latest
 * @suppress PHP0413
 */
class WdfGoogleAIWrapper
{
    private $gconfig;
    private static $serializer = false;

    function __construct($config)
    {
        $this->gconfig = $config;
    }

    /**
     * *
     * @param mixed $prompt
     * @param mixed $options i.e. maxOutputTokens, temperature
     * @param mixed $cache
     * @return mixed
     */
    function Predict($prompt, $options = [], $cache = false)
    {
        global $CONFIG;

        if($cache)
        {
            $cache_key = __METHOD__.'-'.sha1($prompt.'-'.implode('-', array_keys($options)).'-'.implode('-', $options));
            if ($v = cache_get($cache_key))
                return $v;
        }

        // log_debug(__METHOD__, $prompt);

        $ret                     = false;
        $predictionServiceClient = new \Google\Cloud\AIPlatform\V1\Client\PredictionServiceClient(['apiEndpoint' => ifavail($CONFIG['ai']['google'], 'apiendpoint') ?: 'us-central1-aiplatform.googleapis.com']);
        $formattedEndpoint = \Google\Cloud\AIPlatform\V1\Client\PredictionServiceClient::projectLocationPublisherModelName($this->gconfig['project_id'], ifavail($CONFIG['ai']['google'], 'location') ?: 'us-central1', 'google', ifavail($CONFIG['ai']['google'], 'model') ?: 'text-bison');

        // Prepare the request message.
        // $serializer = new \Google\ApiCore\Serializer();
        // $struct = $serializer->decodeMessage(new \Google\Protobuf\Struct(), ['fields' => ['prompt' => ['string_value' => $prompt]]]);
        // $instances = [new \Google\Protobuf\Value(['struct_value' => $struct])];
        $request = (new \Google\Cloud\AIPlatform\V1\PredictRequest())
            ->setEndpoint($formattedEndpoint)
            ->setInstances([$this->__toprotobuf('prompt', $prompt)]);

        foreach($options as $key => $value)
            $request->setParameters($this->__toprotobuf($key, $value));

        // log_debug('request', $request->serializeToJsonString());

        // Call the API and handle any network failures.
        try {
            /** @var \Google\Cloud\AIPlatform\V1\PredictResponse $response */
            $response = $predictionServiceClient->predict($request);
            foreach ($response->getPredictions() as $r)
            {
                $d = json_decode($r->serializeToJsonString(), true);
                if (avail($d, 'content'))
                {
                    $ret = trim($d['content']);
                    break;
                }
            }
            // log_debug('Response data', $response->serializeToJsonString());
        } catch (\Google\ApiCore\ApiException $ex) {
            log_debug('Call failed with message:', $ex->getMessage());
        }

        if(isDev())
            log_debug(__METHOD__, $prompt, $options, $ret);

        if ($cache)
        {
            if ($ret)
                cache_set($cache_key, $ret, $cache);
            else
                cache_del($cache_key);
        }

        return $ret;
    }

    private function __toprotobuf($key, $value)
    {
        if(!self::$serializer)
        self::$serializer = new \Google\ApiCore\Serializer();

        $valuetype = 'string_value';
        if(is_float($value) || is_int($value))
            $valuetype = 'number_value';

        $struct = self::$serializer->decodeMessage(new \Google\Protobuf\Struct(), ['fields' => [$key => [$valuetype => $value]]]);
        return new \Google\Protobuf\Value(['struct_value' => $struct]);
    }
}
