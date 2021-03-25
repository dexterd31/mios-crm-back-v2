<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\CiuService;
use App\Services\NominaService;
use App\Models\Campaing;
use Helpers\MiosHelper;

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
            if (Gate::allows('admin')) {
                $campaigns = $this->nominaService->fetchCampaigns(0);
                return $this->successResponse($campaigns);
            } else {
                // si no, solo mostrar la asociada al usuario
                $user = $this->ciuService->fetchUser(auth()->user()->id)->data;
                try {
                    $campaign = $this->nominaService->fetchCampaign($user->rrhh->campaign_id);
                    return $this->successResponse([$campaign]);
                } catch (\Throwable $th) {
                    // si hay un error, es que la campaña esta desactivada
                    return $this->successResponse([]);;
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

    /**
     * Olme Marin
     * 25-03-2021
     * Método para consultar el listado de las campañas asignadas a un usuario por grupo
     */
    public function campaignsByUser(MiosHelper $miosHelper, $idUser)
    {

        try {
            $groupsIds = [];

            // Se obtienes los grupor por usuarios
            $groups = $miosHelper->groupsByUserId($idUser);
            foreach ($groups as $group) {
                array_push($groupsIds, $group['id']);
            }
            $campaigns = Campaing::where('group_id', $groupsIds)->get();
            $data = $miosHelper->jsonResponse(true, 200, 'campaigns', $campaigns);
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(true, 500, 'message', 'Ha ocurrido un error: ' . $th);
        }

        return response()->json($data, $data['code']);
    }
}
