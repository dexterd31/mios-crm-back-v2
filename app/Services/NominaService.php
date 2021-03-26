<?php

namespace App\Services;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\RequestService;

class NominaService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.nomina.base_uri');
        $this->secret = JWTAuth::getToken();
    }

    public function fetchCampaign($id)
    {
        return $this->request('GET', '/api/campaigns/'.$id)->data;
    }

    public function fetchCampaigns($paginate){
        return $this->request('GET', '/api/campaigns?paginate='. $paginate)->data;
    }

    public function changeCampaignState($id, $state)
    {
        $requestBody = [
            'state'=>$state,

        ];
        return $this->request('PUT', '/api/campaigns/' .$id.'/updateState', $requestBody);
    }
    
}