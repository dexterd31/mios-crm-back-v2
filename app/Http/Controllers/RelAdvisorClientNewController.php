<?php

namespace App\Http\Controllers;

use App\Models\CustomerDataPreload;
use App\Models\RelAdvisorClientNew;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelAdvisorClientNewController extends Controller
{
    use ApiResponse;
    private $relAdvisorModel;

    public function __construct()
    {
        $this->relAdvisorModel = new RelAdvisorClientNew();
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function index($clientNewId)
    {
        return $this->relAdvisorModel->where('client_new_id',$clientNewId)->get();
    }

    /**
     * Muestra un dato en especifico
     *
     * @param  \App\Models\RelAdvisorClientNew  $relAdvisorClientNew
     * @return \Illuminate\Http\Response
     */
    public function show($clientNewId, $rrhhId)
    {
        return $this->relAdvisorModel->where('client_new_id',$clientNewId)->where('rrhh_id',$rrhhId)->first();
    }

    /**
     * Inserta los datos en la tabla para crear la relación
     * @param Request $request : recebe 2 parámetros  1.) client_new_id: el id del cliente 2.) rrhh_id: el id de rrhh
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->validate($request,[
            'client_new_id' => 'required|numeric',
            'rrhh_id' => 'required|numeric'
        ]);

        try {
            return $this->relAdvisorModel->create([
                'client_new_id' => $request->client_new_id,
                'rrhh_id' => $request->rrhh_id,
                'managed' => false
            ]);
        }catch (\Exception $ex){
            return $this->errorResponse($ex->getMessage(),204);
        }
    }

    public function showAssignedClients(int $formId)
    {
        $assignedClients = CustomerDataPreload::adviserFilter(auth()->user()->rrhh_id)->formFilter($formId)
            ->managedFilter(false)->get(['created_at', 'unique_identificator']);
        
        $assignedClients->each(function ($item) {
            $item->unique_indentificator = json_decode($item->unique_indentificator);
        });

        return response()->json(['assigned_clients' => $assignedClients], 200);
    }
}
