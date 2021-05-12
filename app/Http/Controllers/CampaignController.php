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

    public function index(Request $request, MiosHelper $miosHelper)
    {
        //Lita todas las campañas de los grupos a los que pertenece el usuario.
        //Si el usuario es administrador o supervisor, puede ver las campanas inactivas
        try {
            $groupsIds = $miosHelper->groupsByUserId(auth()->user()->id);
            $states = array(1);
            if(Gate::allows('admin') || Gate::allows('supervisor')){
                $states = array_push($states, 0);
            }
            $campaigns = $this->nominaService->fetchSpecificCampaigns($groupsIds, $states);
            return $this->successResponse($campaigns->data);
        }catch (\Throwable $th) {
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

            // Se obtienes los grupor por usuarios
            $groupsIds = $miosHelper->groupsByUserId($idUser);
            $campaigns = Campaing::where('group_id', $groupsIds)->get();
            $data = $miosHelper->jsonResponse(true, 200, 'campaigns', $campaigns);
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(true, 500, 'message', 'Ha ocurrido un error: ' . $th);
        }

        return response()->json($data, $data['code']);
    }
}
