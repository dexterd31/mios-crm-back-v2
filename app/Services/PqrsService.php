<?php

namespace App\Services;

use App\Traits\RequestService;
use Tymon\JWTAuth\Facades\JWTAuth;

class PqrsService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.pqrs.base_uri');
        $this->secret = JWTAuth::getToken();
    }

    public function createUser($rrhh_id, $campaign_id){
        $body = [
            'rrhh_id' => $rrhh_id,
            'state' => 0,
            'campaign_id' =>$campaign_id
        ];
        return $this->jsonRequest('POST', '/api/usuario/', $body);
    }

    public function updateUserState($rrhh_id, $state){
        $body = [
            'state' => $state,
        ];
        return $this->jsonRequest('POST', '/api/usuario/'.$rrhh_id.'/updateState', $body);
    }

    public function createEscalation($asunto_id, $estado_id, $cliente_json, $canal, $preguntas, $radicado_ext, $solicitud){
        $requestBody = [
            'asunto_id' => $asunto_id,
            'estado_id' => $estado_id,
            'cliente_json' => $cliente_json,
            'canal_id' => $canal,
            'preguntas' => $preguntas,
            'radicado_ext' => $radicado_ext,
            'solicitud' => $solicitud
    ];
        return $this->request('POST', '/api/PQRS/', $requestBody);
    }

}

