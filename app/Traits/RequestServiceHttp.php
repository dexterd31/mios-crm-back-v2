<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

trait RequestServiceHttp
{
    public function get($endpoint,$params = []){


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->baseUri.$endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        //Log::info($response);
        curl_close($curl);

        $responseJson = json_decode($response);
        if(!$responseJson->success) throw new Exception("Error Processing Request", 1);

        return $responseJson;

    }
    public function post($endpoint,$params = []){


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->baseUri.$endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>$params,

        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseJson = json_decode($response);
        if(!$responseJson->success) throw new Exception("Error Processing Request", 1);

        return $responseJson;

    }
    public function put(){

    }



}
