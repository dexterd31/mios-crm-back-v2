<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CiuService;
use App\Services\NominaService;

class CampaignController extends Controller
{
    private $ciuService;
    private $nominaService;

    public function __construct(CiuService $ciuService, NominaService $nominaService)
    {
        $this->middleware('auth');
        $this->ciuService = $ciuService;
        $this->nominaService = $nominaService;
    }

    public function index(Request $request)
    {
        $user = $this->ciuService->fetchUser(auth()->user()->id)->data;
        $campaign = $this->nominaService->fetchCampaign($user->rrhh->campaign_id);
        return $this->successResponse([$campaign]);
    }
}
