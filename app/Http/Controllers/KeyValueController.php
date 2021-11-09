<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KeyValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KeyValueController extends Controller
{
    private $keyValueModel;
    public $intentos=5;

    public function __construct()
    {
        $this->keyValueModel = new KeyValue();
    }

    private function save($keysValue)
    {
        // Transaction
        $result=null;
        DB::transaction(function() use($keysValue,&$result) {
            $result=$this->keyValueModel->insert($keysValue);
        },$this->intentos);
        return $result;
    }

    public function createKeysValue($keysValueData, $formId, $idClientNew)
    {
        $keysValue = [];
        foreach ($keysValueData as $keyValueData){
            if(isset($keyValueData["value"])){
                if(is_array($keyValueData["value"])){
                    $keyValueData["value"] = implode(",",$keyValueData["value"]);
                }
                $keyValue = [];
                $keyValue['form_id'] = $formId;
                $keyValue['client_new_id'] = $idClientNew;
                $keyValue['field_id'] = $keyValueData["id"];
                $keyValue['key'] = $keyValueData["key"];
                $keyValue['value'] = $keyValueData["value"];
                $keyValue['description'] = 'crm';
                $keyValue['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                $keyValue['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                array_push($keysValue, $keyValue);
            }
        }
        $keyValuesRequest = new Request();
        $keyValuesRequest->replace([
            "client_new_id" => $idClientNew,
            "form_id" => $formId
        ]);
        $existKeyValue=$this->index($keyValuesRequest);
        if(count($existKeyValue) > 0){
            $deleteRequest = new Request();
            $deleteRequest->replace([
                "form_ids"=>$existKeyValue
            ]);
            $this->delete($deleteRequest);
        }
        return $this->save($keysValue);
    }

     /**
    * @desc COnsulta si existen key values para el form id y client new id
    * @param $request['form_id'] -> id del formulario al que pertenecen los key values
    * @return $request['client_new_id'] -> id del cliente que debemos buscar
    * @return array -> Key_values matchs
    * @author Leonardo Giraldo Quintero
    */
    public function index(Request $request){
        return $this->keyValueModel->where('form_id',$request['form_id'])->where('client_new_id',$request['client_new_id'])->pluck('id')->all();
    }

    /**
    * @desc Borra los key values asociados al formulario y al client_new_id
    * @param $request['form_id'] -> id del formulario al que pertenecen los key values a borrar
    * @return $request['client_new_id'] -> id del cliente que debemos buscar y borrar
    * @return integer -> 1 borrado 0 no borrado
    * @author Leonardo Giraldo Quintero
    */
    public function delete(Request $request){
        //try{
            $this->keyValueModel->whereIn('id',$request['form_ids'])->delete();
        /*}catch(Throwable $e){
            return $e->getMessage();
        }*/
    }
}
