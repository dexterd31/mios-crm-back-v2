<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KeyValue;
use Carbon\Carbon;

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
        $this->keyValueModel->insert($keysValue);
    }

    public function createKeysValue($keysValueData, $formId, $idClientNew)
    {
        $keysValue = [];
        foreach ($keysValueData as $keyValueData)
        {
            $keyValue = [];
            $keyValue['form_id'] = $formId;
            $keyValue['client_new_id'] = $idClientNew;
            $keyValue['field_id'] = $keyValueData["id"];
            $keyValue['key'] = $keyValueData["key"];
            $keyValue['value'] = $keyValueData["value"];
            $keyValue['description'] = null;
            $keyValue['client_id'] = 0;
            $keyValue['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
            $keyValue['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
            array_push($keysValue, $keyValue);
        }
        $this->save($keysValue);
    }

}
