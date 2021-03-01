<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Log;
use Exception;

trait RequestService
{

    public function request($method, $requestUrl, $formParams = [], $headers = [])
    {
        try{
            Log::info($this->baseUri);
            $client = new Client([
            'base_uri' => $this->baseUri
            ]);
            if (isset($this->secret)) {
                $headers['Authorization'] = $this->secret;
            }

            $response = $client->request($method, env('LOCAL') ? 'public/'.$requestUrl : $requestUrl,
                [
                    'form_params' => $formParams,
                    'headers' => $headers,
                    'timeout' => 10,
                    'connect_timeout' => 10
                ]
            );
            
            return json_decode($response->getBody()->getContents());
        }
        catch (Exception $e){
            Log::info($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}