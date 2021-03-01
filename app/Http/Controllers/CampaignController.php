<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        try {
            // si es admin mostrar todas las campañas
            if(Gate::allows('admin')){
                $campaigns = $this->nominaService->fetchCampaigns(0);
                return $this->successResponse($campaigns);
            } else{
                // si no, solo mostrar la asociada al usuario
                $user = $this->ciuService->fetchUser(auth()->user()->id)->data;
                $campaign = $this->nominaService->fetchCampaign($user->rrhh->campaign_id);
                
                if($campaign){
                    return $this->successResponse([$campaign]);
                } else {
                    return $this->successResponse([]);
                }
                
            }
        } catch (\Throwable $th) {
            return $this->errorResponse('Ocurrio un error al intentar mostrar las campañas.', 500);
        }  
    }

    public function updateState(Request $request, $id)
    {
        try {
            $this->nominaService->changeCampaignState($id, $request->state);
            return $this->successResponse("Estado de campaña cambiado exitosamente");
        } catch (\Throwable $th) {
            return $this->errorResponse('Ocurrio un error al intentar cambiar el estado de la campaña.', 500);
        }
    }
}
