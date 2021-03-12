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
        $this->secret = JWTAuth::getToken()->get();
    }

    public function fetchUser($id){
        return $this->request('GET', '/api/users/'.$id);
    }

}

