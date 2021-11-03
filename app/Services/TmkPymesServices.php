<?php

namespace App\Services;

use App\Http\Controllers\ClientNewController;
use App\Http\Controllers\KeyValueController;
use App\Http\Controllers\FormAnswerController;
use App\Http\Controllers\UploadController;
use App\Models\NotificationLeads;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TmkPymesServices
{
    private $leadFields;
    private $productVicidial;
    private $tokenVicidial;


    /**
     * @param $formId
     * @return string
     */
    public function setAccount($formId,$fieldFillIn)
    {
        //try{
            $answerFields = (Object)[];
            $errorAnswers = [];
            $formAnswerClient=[];
            $formAnswerClientIndexado=[];
            $respuesta=(Object)[];
            $uploadController = new UploadController();
            foreach($this->leadFields as $key=>$lead){
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
                        array_push($errorAnswers,$dataValidate['message']);
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
                $formAnswerController=new FormAnswerController();
                $formAnswerSave=$formAnswerController->create($clientNew->id,$formId,$formAnswerClient,$formAnswerClientIndexado,"upload");
                if(isset($formAnswerSave->id)){
                    if(isset($answerFields->preload)){
                        $keyValuesController= new KeyValueController();
                        $keyValues=$keyValuesController->createKeysValue($answerFields->preload,$formId,$clientNew->id);
                        if(!isset($keyValues)){
                            array_push($errorAnswers,"No se han podido insertar keyValues para el cliente ".$client->id);
                        }
                    }
                    NotificationLeads::create([
                        'client_id' => 0,
                        'phone' => $this->leadFields['telefono'],
                        'form_id' => $formId,
                        'createdtime' => Carbon::now()->format('Y-m-d H:i:s'),
                        'id_datacrm' => $this->leadFields['razon_social'],
                        'client_new_id' => $clientNew->id,
                        'lead_information' => json_encode($this->leadFields)
                    ]);
                    $newLeadVicidial = array(
                        "producto"=>$this->productVicidial,
                        "token_key"=>$this->tokenVicidial,
                        "Celular"=>$this->leadFields['telefono']
                    );
                    $this->newLeadVicidial($newLeadVicidial);
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
        $string = str_replace(' ', '-', $string);
        $unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');
        $str = strtr($string, $unwanted_array);
        return trim($str);

    }

    /**
     * @return string[]
     */
    public function leadColumns(): array{
        return [
            "nombre",
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
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->leadFields;
    }

    /**
     * @param mixed $leadFields
     */
    public function setLeadFields($leadFields): void
    {
        $this->leadFields = $leadFields;
        $this->productVicidial="TMK";
        $this->tokenVicidial="TmK202111031233";
    }

    private function newLeadVicidial($params){
        $vicidialInsertion=Http::post(env('SERVICE_SYNC_VICIDIAL').'/cos/services',$params);
        \Log::notice("Incersion Vicidial TMK Pymes: ".$params['Celular'].$vicidialInsertion);
    }

}
