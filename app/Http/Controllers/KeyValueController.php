<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KeyValue;


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
			$this->setKeyValueModel(new KeyValueModel());
		}
		return $this->keyValueModel;
	}

    private function save($keyValue)
    {
        $this->getKeyValueModel();
        $this->keyValueModel->insert([$keyValue]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
            'client_new_id' => 'required|integer',
            'field_id' => 'required|integer',
            'key' => 'required|string',
            'unique_indentificator' => 'required|json'
        ]);

        if($validator->fails())
        {
            $data = $validator->errors()->all();
        }

        $this->save($request);
    }

}
