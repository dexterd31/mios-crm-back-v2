<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\CiuService;
use App\Services\NominaService;
use Helpers\MiosHelper;
use Log;


class CampaignController extends Controller
{
    private $ciuService;
    private $nominaService;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function setCiuService($ciuService)
	{
		$this->ciuService = $ciuService;
	}

    public function getCiuService()
	{
		if($this->ciuService == null)
		{
			$this->setCiuService(new CiuService());
		}
		return $this->ciuService;
	}

    public function setNominaService($nominaService)
	{
		$this->nominaService = $nominaService;
	}

    public function getNominaService()
	{
		if($this->nominaService == null)
		{
			$this->setNominaService(new NominaService());
		}
		return $this->nominaService;
	}

    public function index()
    {
        //Litar todas las campañas de los grupos a los que pertenece el usuarioi
        //Si el usuario es administrador o supervisor, puede ver las campanas inactivas
        try {
            //$this->getCiuService();
            $this->getNominaService();
            //$user = $this->ciuService->fetchUser($this->authUser()->id)->data;
            //Se traen las campañas por el id de campaña
            $campaign = $this->nominaService->fetchSpecificCampaigns([$this->authUser()->rrhh->campaign_id]);
            /**
             * @author: Leogiraldoq
             * Se quitan los elementos inecesarios en para el front,
             * ? se realiza for para tener en cuenta el momento que se listen mas de una campaña (SuperAdministrador)
            */
            for($c=0;$c<count($campaign->data);$c++){
                unset($campaign->data[$c]->rrhh_id);
                unset($campaign->data[$c]->code);
                unset($campaign->data[$c]->created_at);
                unset($campaign->data[$c]->updated_at);
            }
            return $this->successResponse($campaign->data);
        } catch (\Throwable $th) {
            return $this->errorResponse('Ocurrio un error al intentar mostrar las campañas.', 500);
        }
    }


    public function updateState(Request $request, $id)
    {
        try {
            $this->getNominaService();
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
    public function campaignsByUser(MiosHelper $miosHelper, $idUser, GroupController $groupController)
    {
        try {
            // Se obtienes los grupor por usuarios
            $campaignsIds = $this->authUser()->rrhh->campaign_id;
            $this->getNominaService();
            $campaigns = $this->nominaService->fetchSpecificCampaigns($campaignsIds);
            $data = $miosHelper->jsonResponse(true, 200, 'campaigns', $campaigns);
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(true, 500, 'message', 'Ha ocurrido un error: ' . $th);
        }

        return response()->json($data, $data['code']);
    }
}
