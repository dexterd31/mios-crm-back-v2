<?php

namespace App\Http\Controllers;

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
        $this->dataCrmService->getAccounts(1);
    }

    public function getFields(){
        $this->dataCrmService->getFields(2);
    }
}
