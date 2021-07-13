<?php

namespace App\Services;

use App\Traits\RequestService;
use Tymon\JWTAuth\Facades\JWTAuth;


class SyncServices{

    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
       // $this->secret = JWTAuth::getToken();
    }

    public function login($baseUri,$params){
        $this->baseUri = $baseUri;
        return $this->request('POST','auth/login',$params);
    }

    public function getFormById($baseUri,$id,$token){
        $this->baseUri = $baseUri;

        $this->secret = $token;
        return $this->request('GET','searchform/'.$id);
    }


}
