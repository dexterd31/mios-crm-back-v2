<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

trait RequestService
{

    public function request($method, $requestUrl, $formParams = [], $headers = [])
    {
        try{
            $client = new Client([
                'verify' => false,
                'base_uri' => $this->baseUri
            ]);
            if (isset($this->secret)) {
                $headers['Authorization'] = 'Bearer '.$this->secret;
                $headers['Content-Type'] = 'application/json';
            }

            $data = [
                'headers' => $headers,
                'timeout' => 10,
                'connect_timeout' => 10,
                'json' => $formParams
            ];

            $response = $client->request($method, env('LOCAL') ? 'public/'.$requestUrl : $requestUrl, $data);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e){
            Log::error($e->getMessage());
            return json_decode($e->getMessage());
        }
    }

    /**
     * Metodo para hacer una peticion pero en ves de enviar
     * los parametros como application/x-www-form-urlencoded
     * los envia como json en el cuerpo de la peticion
    */
    public function jsonRequest($method, $requestUrl, $formParams = [], $headers = [])
    {
        try{
            Log::info($this->baseUri);
            $client = new Client([
                'verify' => false,
                'base_uri' => $this->baseUri
            ]);

            // Esto solo aplica para ciu, ya que en el login no hay token en las cabeceras.
            // en ese caso verificar si el usuario esta logeado y generar un token basado en el id
            if(empty($this->secret)){
                $this->secret = auth()->tokenById(auth()->user()->id);
            }

            if (isset($this->secret)) {
                $headers['Authorization'] = 'Bearer '.$this->secret;
            }

            $response = $client->request($method, env('LOCAL') ? 'public/'.$requestUrl : $requestUrl,
                [
                    'json' => $formParams,
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
