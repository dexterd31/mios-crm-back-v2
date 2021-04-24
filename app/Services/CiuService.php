<?php

namespace App\Services;

use App\Traits\RequestService;
use Tymon\JWTAuth\Facades\JWTAuth;

class CiuService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.ciu.base_uri');
        $this->secret = JWTAuth::getToken();
    }

    public function fetchUser($id){
        return $this->request('GET', '/api/users/'.$id);
    }

    /**
     * buscar usuario por id de rrhh
     */
    public function fetchUserByRrhhId($id){
        return $this->request('GET', '/api/users/showByRrhhId/'.$id)->data;
    }

}

