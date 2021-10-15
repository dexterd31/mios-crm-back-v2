<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KeyValue;
use Carbon\Carbon;
use Throwable;

class KeyValueController extends Controller
{
    private $keyValueModel;
    public function setKeyValueModel($keyValueModel)
	{
		$this->keyValueModel = $keyValueModel;
	}

    public function getKeyValueModel()
	{
		if($this->keyValueModel == null)
		{
			$this->setKeyValueModel(new KeyValue());
		}
		return $this->keyValueModel;
	}

    private function save($keysValue)
    {
        $this->getKeyValueModel();
        return $this->keyValueModel->insert($keysValue);
    }

    public function createKeysValue($keysValueData, $formId, $idClientNew)
    {
        $keysValue = [];
        foreach ($keysValueData as $keyValueData){
            if($keyValueData["value"] !== null && $keyValueData["value"] !==''){
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
        if(count($existKeyValue)>0){
            $this->delete($keyValuesRequest);
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
        $this->validate($request,[
            'form_id' => 'required|integer',
            'client_new_id' => 'required|integer',
        ]);
        $this->getKeyValueModel();
        return $this->keyValueModel->where('form_id',$request['form_id'])->where('client_new_id',$request['client_new_id'])->get();
    }

    /**
    * @desc Borra los key values asociados al formulario y al client_new_id
    * @param $request['form_id'] -> id del formulario al que pertenecen los key values a borrar
    * @return $request['client_new_id'] -> id del cliente que debemos buscar y borrar
    * @return integer -> 1 borrado 0 no borrado
    * @author Leonardo Giraldo Quintero
    */
    public function delete(Request $request){
        $this->validate($request,[
            'form_id' => 'required|integer',
            'client_new_id' => 'required|integer',
        ]);
        try{
            $this->getKeyValueModel();
            return $this->keyValueModel->where('form_id',$request['form_id'])->where('client_new_id',$request['client_new_id'])->delete();
        }catch(Throwable $e){
            return $e->getMessage();
        }
    }
}
