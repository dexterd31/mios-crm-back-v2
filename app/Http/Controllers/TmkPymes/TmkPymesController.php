<?php

namespace App\Http\Controllers\TmkPymes;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\FormController;
use stdClass;
use App\Models\NotificationLeads;
use App\Http\Controllers\ClientNewController;
use App\Http\Controllers\KeyValueController;
use App\Http\Controllers\UploadController;
use Carbon\Carbon;
use App\Models\FormAnswer;
use App\Models\FormAnswerLog;
use Illuminate\Support\Facades\Http;

class TmkPymesController extends Controller
{
    private $formId;
    private $idFieldsInFormLead;
    private $leadFields;
    private $productVicidial;
    private $tokenVicidial;
    private $leadTMK;
    private $leadTMKModify;

    public function __construct()
    {
        $this->formId = env('TMK_PYMES_WB_FORM_ID', 2);
        $this->idFieldsInFormLead=(Object)[
            'nombre' => 1635436624162,
            'apellidos' => 9935436624162,
            'razon_social' => 1635437152258,
            'telefono' => 1635436912538,
            'utm_campaign' => 1635518784366,
            'tipo_documento' => 1637160407586,
            'numero_documento' => 1635549672268,
            'id' => 1635436930539
        ];
        $this->productVicidial="TMK";
        $this->tokenVicidial="TmK202111031233";
        $this->leadColumns=[
            "nombre",
            "apellidos",
            "razon_social",
            "nit",
            "ciudad",
            "email",
            "tipo_telefono",
            "telefono",
            "extension",
            "producto",
            "optin",
            "glid",
            "utm_source",
            "utm_campaign",
            "utm_medium",
            "queu_promo",
            "referencia_producto",
            "canal_trafico",
            "resumen_plan"
        ];
    }

    /**
     * Metodo encargado de realizar el guardado del lead de tmk
     * @param Request $request
     * @return JsonResponse|void
     */
    public function store(Request $request)
    {
        //try {
            $validator = validator($request->all(), [
                'ciudad' => 'required',
                'telefono' => 'required',
                'optin' => 'required',
            ]);
            $this->leadTMK=$request->all();
            $this->leadTMKModify=$request->all();
            $this->leadTMKModify['tipo_documento']="";
            $this->leadTMKModify['numero_documento']="";
            if(isset($this->leadTMKModify['email']) && $this->leadTMKModify['email'] != ''){
                $identification=explode("*",$this->leadTMKModify['email']);
                if(isset($identification[1]))$this->leadTMKModify['tipo_documento']=$identification[0];
                if(isset($identification[1]))$this->leadTMKModify['numero_documento']=$identification[1];
            }
            if(isset($this->leadTMKModify['razon_social'])){
                $idRazonSocial=explode("*",$this->leadTMKModify['razon_social']);
                if(isset($idRazonSocial[1])){
                    $this->leadTMKModify['razon_social']=$idRazonSocial[1];
                    $this->leadTMKModify['id']=$idRazonSocial[0];
                }else{
                    $this->leadTMKModify['id']=$this->leadTMKModify['razon_social'];
                }
            }
            if ($validator->fails()) return $this->responseTmk(implode(", ", $validator->errors()->all()), -1);
            $acount=(Object)[];
            $acount=$this->setAccount($this->formId,$this->setFieldToFillIn());
            return $this->responseTmk($acount->message, $acount->code);
        /*} catch (\Throwable $th) {
            Log::error("Code: {$th->getCode()}, Message: {$th->getMessage()}, File: {$th->getFile()}, Line: {$th->getLine()}");
            return $this->responseTmk($th->getMessage(), -1);
        }*/
    }

    public function setAccount($formId,$fieldFillIn)
    {
        //try{
            $answerFields = (Object)[];
            $errorAnswers = [];
            $formAnswerClient=[];
            $formAnswerClientIndexado=[];
            $respuesta=(Object)[];
            $uploadController = new UploadController();
            foreach($this->leadTMKModify as $key=>$lead){
                if(isset($fieldFillIn[$key])){
                    $dataValidate=$uploadController->validateClientDataUpload($fieldFillIn[$key],$this->cleanString($lead));
                    if($dataValidate->success){
                        foreach($dataValidate->in as $in){
                            if (!isset($answerFields->$in)){
                                $answerFields->$in=[];
                            }
                            array_push($answerFields->$in,$dataValidate->$in);
                        }
                        array_push($formAnswerClient,$dataValidate->formAnswer);
                        array_push($formAnswerClientIndexado,$dataValidate->formAnswerIndex);
                    }else{
                        array_push($errorAnswers,$dataValidate->message);
                    }
                }

            }
            $clientNewRequest = new Request();
            $clientNewRequest->replace([
                "form_id" => $formId,
                "information_data" => json_encode($answerFields->informationClient),
                "unique_indentificator" => json_encode($answerFields->uniqueIdentificator[0]),
            ]);
            $clienNewController = new ClientNewController();
            $clientNew = $clienNewController->create($clientNewRequest);
            if(isset($clientNew->id)){
                $formAnswerSave = new FormAnswer([
                    'rrhh_id' => 1,
                    'channel_id' => 1,
                    'form_id' => $formId,
                    'structure_answer' => json_encode($formAnswerClient),
                    'client_new_id' => $clientNew->id,
                    "form_answer_index_data" => json_encode($formAnswerClientIndexado),
                    'tipification_time' => "upload"
                ]);
                $formAnswerSave->save();
                if(isset($formAnswerSave->id)){
                    $log = new FormAnswerLog();
                    $log->form_answer_id = $formAnswerSave->id;
                    $log->structure_answer = $formAnswerSave->structure_answer;
                    $log->rrhh_id = $formAnswerSave->rrhh_id;
                    $log->save();
                    if(isset($answerFields->preload)){
                        $keyValuesController= new KeyValueController();
                        $keyValues=$keyValuesController->createKeysValue($answerFields->preload,$formId,$clientNew->id);
                        if(!isset($keyValues)){
                            array_push($errorAnswers,"No se han podido insertar keyValues para el cliente ".$clientNew->id);
                        }
                    }
                    NotificationLeads::create([
                        'client_id' => 0,
                        'phone' => $this->leadTMK['telefono'],
                        'form_id' => $formId,
                        'createdtime' => Carbon::now()->format('Y-m-d H:i:s'),
                        'client_new_id' => $clientNew->id,
                        'lead_information' => json_encode($this->leadTMK)
                    ]);
                    if(env('APP_ENVIROMENT')=='prod' || env('APP_ENVIROMENT')=='qa'){
                        $newLeadVicidial = array(
                            "producto"=>$this->productVicidial,
                            "token_key"=>$this->tokenVicidial,
                            "Celular"=>$this->leadTMK['telefono']
                        );
                        $this->newLeadVicidial($newLeadVicidial);
                    }
                    $respuesta->message="SUCCESS";
                    $respuesta->code=0;
                  } else {
                    $respuesta->message="No se ha podido crear la respuesta: ".$formAnswerSave;
                    $respuesta->code=-1;
                  }
            }else{
                $respuesta->message="No se ha podido crear el cliente: ".$clientNew;
                $respuesta->code=-1;
            }
            return $respuesta;
        /*}catch(\Throwable $th){
            response()->json(['message' => $th->getMessage(),'code' => -1]);
        }*/
    }


    private function cleanString($string)
    {
        $unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');
        $str = strtr($string, $unwanted_array);
        return trim($str);

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

    private function newLeadVicidial($params){
        Http::post(env('SERVICE_SYNC_VICIDIAL').'/cos/services',$params);
    }
}

