<?php

namespace App\Http\Controllers\TmkPymes;

use App\Http\Controllers\Controller;
use App\Services\TmkPymesServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use App\Http\Controllers\FormController;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class TmkPymesController extends Controller
{
    private $tmkPymesServices;
    private $formId;
    private $idFieldsInFormLead;

    public function __construct(TmkPymesServices $pymesServices)
    {
        $this->tmkPymesServices = $pymesServices;
        $this->formId = env('TMK_PYMES_WB_FORM_ID', 2);
        $this->idFieldsInFormLead=(Object)[
            'nombre' => 1635436624162,
            'razon_social' => 1635436930539,
            'nit' => 1635436893514,
            'telefono' => 1635436912538,
            'utm_campaign' => 1635518784366
        ];
    }

    /**
     * Metodo que retorna el token para que el cliente claro
     * @return JsonResponse
     */
    public function generateToken(): JsonResponse
    {
        try {
            $payload = JWTFactory::customClaims(['rrhh_id' => 1, 'form_id' => $this->formId]);
            return response()->json(['clientToken' => 'Bearer '. JWTAuth::encode($payload->make())->get() ?? '']);
        } catch (\Throwable $th) {
            Log::error("Code: {$th->getCode()}, Message: {$th->getMessage()}, File: {$th->getFile()}, Line: {$th->getLine()}");
            return $this->responseTmk($th->getMessage(), $th->getCode());
        }
    }

    /**
     * Metodo encargado de realizar el guardado del lead de tmk
     * @param Request $request
     * @return JsonResponse|void
     */
    public function store(Request $request)
    {
        //$this->middleware('auth');
        try {
            $validator = validator($request->all(), [
                'razon_social' => 'required',
                'ciudad' => 'required',
                'telefono' => 'required',
                'optin' => 'required',
            ]);
            if ($validator->fails()) return $this->responseTmk(implode(", ", $validator->errors()->all()), -1);
            $this->tmkPymesServices->setLeadFields($request->only($this->tmkPymesServices->leadColumns()));
            $acount=(Object)[];
            $acount=$this->tmkPymesServices->setAccount($this->formId,$this->setFieldToFillIn());
            return $this->responseTmk($acount->message, $acount->code);
        } catch (\Throwable $th) {
            Log::error("Code: {$th->getCode()}, Message: {$th->getMessage()}, File: {$th->getFile()}, Line: {$th->getLine()}");
            return $this->responseTmk($th->getMessage(), -1);
        }
    }

    private function setFieldToFillIn(){
        $idFields=[];
        foreach($this->idFieldsInFormLead as $key=>$wsElement){
            $object = new stdClass();
            $object->id = $this->idFieldsInFormLead->$key;
            array_push($idFields,$object);
        }
        $formController = new FormController();
        $prechargables=$formController->getSpecificFieldForSection($idFields,$this->formId);
        $fullField=[];
        foreach($this->idFieldsInFormLead as $k=>$field){
            foreach($prechargables as $preload){
                if($field==$preload->id){
                    $fullField[$k]=$preload;
                    continue;
                }
            }
        }
        return $fullField;
    }
}

