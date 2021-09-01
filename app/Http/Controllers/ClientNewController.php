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
;
        foreach ($request->information_data as $informationData)
        {
            $informationData = json_decode($request->informationData);
            $clientNewQuery = $clientNewQuery->where("unique_indentificator->key", $informationData->key)
                ->where("unique_indentificator->value", $informationData->value);
        }

        return $this->clientNewModel->get();
    }


    /**
     * @desc Funcion que lista un cliente ya sea por su id (client_id) o por su identificador unico (unique_indentificator)
     * @param form_id Integer Required: Id del formulario donde desea consultar el cliente
     * @param unique_indentificator Objeto Requerido si no se envia client_new_id: Objeto con el id del field unique y el valor
     * @param client_new_id Integer Requerido si no se envia unique_indentificator: Id del cliente que desea consultar
     * @return \Illuminate\Http\Response Objeto con los datos almacenados del cleinte
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
            'unique_indentificator' => 'json',
            'information_data' => 'array',
        ]);

        $validatorId = Validator::make($request->all(),[
            'client_new_id' => 'required|integer'
        ]);

        if($validator->fails() && $validatorId->fails())
        {
            return array_merge($validator->errors()->all(), $validatorId->errors()->all());
        }

        $clientNewQuery = $this->getClientNewModel();
        $clientNewQuery = $clientNewQuery->where("form_id", $request->form_id);
        if($request->client_new_id)
        {
            return $this->clientNewModel->find($request->client_new_id);
        }

        $informations_data = $request->information_data;
        if($informations_data)
        {
            foreach($informations_data as $informationData)
            {
                $informationDataJson = json_encode([
                    "id"=> $informationData["id"],
                    "key"=> $informationData["key"],
                    "value"=> $informationData["value"],
                ]);
                $clientNewQuery = $clientNewQuery
                ->whereRaw("json_contains(lower(information_data), lower('$informationDataJson'))");
            }
        }

        if($request->unique_indentificator)
        {
            $unique_indentificator = json_decode($request->unique_indentificator);
            $clientNewQuery = $clientNewQuery->where("unique_indentificator->id", $unique_indentificator->id)
                ->where("unique_indentificator->value", $unique_indentificator->value);
        }
        \Log::info($clientNewQuery->first());
        return $clientNewQuery->first();
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
            $clientNewRequest = new Request();
            $clientNewRequest->replace([
                "form_id" => $request->form_id,
                "unique_indentificator" => $request->unique_indentificator,
            ]);
            $clientsNew = $this->index($clientNewRequest);
            if($clientsNew && isset($clientsNew->id))
            {
                $data = $this->update($request, $clientsNew);
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
