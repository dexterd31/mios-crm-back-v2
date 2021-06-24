<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use Illuminate\Http\Request;
use App\Services\DataCRMService;

class SandboxController extends Controller
{
    private $dataCrmService;
    public function __construct(DataCRMService $dataCrmService)
    {
        $this->dataCrmService = $dataCrmService;
    }
    public function getContactsFromDataCRM(){

        $formsDataCRM = ApiConnection::where('api_type',10)->where('status',1)->get();
        foreach ($formsDataCRM as $key => $value) {
            $this->dataCrmService->getAccounts($value->form_id);
        }

    }
}
