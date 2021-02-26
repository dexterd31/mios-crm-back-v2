<?php

namespace App\Services;

use App\Traits\RequestService;
class NominaService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.nomina.base_uri');
    }

    public function fetchCampaign($id){
        return (object)['name' => 'Claro Ventas', 'description' => 'Claro Ventas', 'id' => 1];
        //return $this->request('GET', '/api/users/'.$id);
    }

    public function fetchCampaigns(){
        return array((object)['name' => 'Claro Ventas', 'description' => 'Claro Ventas', 'id' => 1]);
        //return $this->request('GET', '/api/users/'.$id);
    }

    
}