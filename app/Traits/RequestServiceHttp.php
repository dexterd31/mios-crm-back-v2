<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

trait RequestServiceHttp
{
    public function get($endpoint,$params = []){


            $response = Http::get($this->baseUri.$endpoint,$params);

            if($response->successful()){
                return $response->json();
            }
            if($response->failed()){
                throw new Exception("Error Processing Request", 1);
            }


    }
    public function post($endpoint,$params = []){
        $response = Http::post($this->baseUri.$endpoint, $params);
        Log::info($response->clientError());
        Log::info($response->serverError());

        if($response->successful()){
            return $response->json();
        }
        if($response->failed()){
            throw new Exception("Error Processing Request", 1);
        }
    }
    public function put(){

    }

}
