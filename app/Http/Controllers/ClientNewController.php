<?php

namespace App\Http\Controllers;

use App\Models\ClientNew;
use Illuminate\Http\Request;
use Helper\MiosHelper;
use Illuminate\Support\Facades\Validator;
use Log;

class ClientNewController extends Controller
{
    private $clientNewModel;

    public function setClientNewModel($clientNewModel)
	{
		$this->clientNewModel = $clientNewModel;
	}

    public function getClientNewModel()
	{
		if($this->clientNewModel == null)
		{
			$this->setClientNewModel(new ClientNew());
		}
		return $this->clientNewModel;
	}

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
            "information_data" => 'array',
            "unique_indentificator" => 'json',
        ]);

        if($validator->fails())
        {
            return $validator->errors()->all();
        }

        $this->getClientNewModel();
        $clientNewQuery = $this->clientNewModel->where("form_id", $request->form_id);

        if($request->unique_indentificator)
        {
            $unique_indentificator = json_decode($request->unique_indentificator);
            $clientNewQuery = $clientNewQuery->where("unique_indentificator->key", $unique_indentificator->key)
                ->where("unique_indentificator->value", $unique_indentificator->value);
        }

        foreach ($request->information_data as $informationData)
        {
            $informationData = json_decode($request->informationData);
            $clientNewQuery = $clientNewQuery->where("unique_indentificator->key", $informationData->key)
                ->where("unique_indentificator->value", $informationData->value);
        }

        return $this->clientNewModel->get();
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
            "unique_indentificator" => 'required|json',
        ]);

        $validatorId = Validator::make($request->all(),[
            'client_new_id' => 'required|integer'
        ]);

        if($validator->fails() && $validatorId->fails())
        {
            return $validator->errors()->all();
        }

        $this->getClientNewModel();
        if($request->client_new_id)
        {
            return $this->clientNewModel->find($request->client_new_id);
        }
        $unique_indentificator = json_decode($request->unique_indentificator);
        return $this->clientNewModel->where("form_id", $request->form_id)->where("unique_indentificator->id", $unique_indentificator->id)
            ->where("unique_indentificator->value", $unique_indentificator->value)->first();
    }

    // Descripción: Función que recibe un objeto y realiza las validaciones y arreglos a
    // la data para pasar a la función saveClient para que sean almacenados en la tabla clients_new 
    // Parámetros:
    // Array Datos de uno o varios clientes en un objeto 
    // Retorna: Objeto con un parámetro estado el cual será true or false dependiendo del resultado 
    // del proceso, y un parámetro data con el objeto de  creación del cliente ósea los datos almacenados en la tabla clients_new. 
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
            'information_data' => 'required|json',
            'unique_indentificator' => 'required|json'
        ]);

        if($validator->fails())
        {
            $data = $validator->errors()->all();
        }
        else
        {
            $clientNew = $this->index($request);
            Log::info(json_encode($clientNew));
            if($clientNew && isset($clientNew->id))
            {
                $data = $this->update($request, $clientNew);
            }else
            {
                $data = $this->save($request);
            }
        }

        return $data;
    }

    private function save($clientNewData)
    {
        $clientNew = new ClientNew([
            "form_id" => $clientNewData->form_id,
            "information_data" => $clientNewData->information_data,
            "unique_indentificator" => $clientNewData->unique_indentificator,
        ]);
        $clientNew->save();
        return $clientNew;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClientNew  $clientNew
     * @return \Illuminate\Http\Response
     */
    public function show(ClientNew $clientNew)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ClientNew  $clientNew
     * @return \Illuminate\Http\Response
     */
    public function edit(ClientNew $clientNew)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ClientNew  $clientNew
     * @return \Illuminate\Http\Response
     */
    private function update(Request $request, ClientNew $clientNew)
    {
        $clientNew->information_data = $request->information_data;
        $clientNew->save();
        return $clientNew;
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ClientNew  $clientNew
     * @return \Illuminate\Http\Response
     */
    public function destroy(ClientNew $clientNew)
    {
        //
    }
}
