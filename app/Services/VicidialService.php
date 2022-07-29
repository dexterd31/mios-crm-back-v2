<?php

namespace App\Services;

use App\Traits\RequestService;
use Tymon\JWTAuth\Facades\JWTAuth;

class VicidialService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.vicidial.base_uri');
        $this->secret = JWTAuth::getToken();
    }

    public function sendLead(array $data)
    {
        return $this->request('POST', '/webservice-dinamico/montechelo/services', $data);
    }
}
