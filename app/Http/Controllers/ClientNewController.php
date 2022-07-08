<?php

namespace App\Http\Controllers;

use App\Models\ClientNew;
use App\Models\Escalation;
use Illuminate\Http\Request;
use Helper\MiosHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Cast\Object_;

class ClientNewController extends Controller
{
    private $clientNewModel;

    public function __construct()
    {
        // $this->middleware('auth');
        $this->middleware('auth', ['except' => ['show']]);
    }

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

        if($validator->fails() && $validatorId->fails()){
            $error["error"] = array_merge($validator->errors()->all(), $validatorId->errors()->all());
            return $error["error"];
        }

        $clientNewQuery = $this->getClientNewModel();
        $clientNewQuery = $clientNewQuery->where("form_id", $request->form_id);
        if($request->client_new_id){
            return $this->clientNewModel->find($request->client_new_id);
        }

        if($request->unique_indentificator)
        {
            $unique_indentificator = json_decode($request->unique_indentificator);
            $clientNewQuery = $clientNewQuery->whereJsonContains("unique_indentificator",["id"=>$unique_indentificator->id]);
            $uniqueValueInt=intval($unique_indentificator->value);
            if(gettype($uniqueValueInt) == 'integer'){
                $clientNewQuery->where(function ($query) use ($uniqueValueInt,$unique_indentificator){
                    $query->whereJsonContains("unique_indentificator",["value"=>$unique_indentificator->value])
                    ->orWhereJsonContains("unique_indentificator",["value"=>$uniqueValueInt]);
                });
            }else{
                $clientNewQuery->whereJsonContains("unique_indentificator",["value"=>$unique_indentificator->value]);
            }
        }
        
        $informations_data = $request->information_data;

        if($informations_data) {
            $clientNewQuery = $clientNewQuery->get()->filter(function ($client) use ($informations_data) {
                $foundCounter = 0;
                foreach($informations_data as $informationData) {
                    foreach (json_decode($client->information_data) as $data) {
                        $value = strtolower(strval($data->value));
                        $infoValue = strtolower(strval($informationData["value"]));
                        
                        if ($data->id == $informationData["id"] && $value == $infoValue) {
                            $foundCounter++;
                            break;
                        }
                    }
                }
                
                return $foundCounter ? true : false;
            });

        }

        return $clientNewQuery->first();
    }

    //TODO: crear método para consultar el indice con la relación

    // Descripción: Función que recibe un objeto y realiza las validaciones y arreglos a
    // la data para pasar a la función saveClient para que sean almacenados en la tabla clients_new
    // Parámetros:
    // Array Datos de uno o varios clientes en un objeto
    // Retorna: Objeto con un parámetro estado el cual será true or false dependiendo del resultado
    // del proceso, y un parámetro data con el objeto de  creación delto cliente ósea los datos almacenados en la tabla clients_new.
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
            'information_data' => 'required|json',
            'unique_indentificator' => 'required|json'
        ]);

        if($validator->fails()){
            $data = $validator->errors()->all();
        } else {
            $clientNewRequest = new Request();
            $clientNewRequest->replace([
                "form_id" => $request->form_id,
                "unique_indentificator" => $request->unique_indentificator,
            ]);

            $clientsNew = $this->index($clientNewRequest);

            if($clientsNew && isset($clientsNew->id)) {
                $data = $this->update($request, $clientsNew);
            } else {
                $data = $this->save($request);
            }
        }
        return $data;
    }

    private function save($clientNewData)
    {
        $informationDataClient = [];
        $informationData = json_decode($clientNewData->information_data);
        foreach($informationData as $data){
            /*if(gettype($data->value)!=="string"){
                $data->value=strval($data->value);
            }*/
            array_push($informationDataClient, (Object)
            [
                "id"=> $data->id,
                "value"=> $data->value,
            ]);
        }

        $uniqueIdentificator=json_decode($clientNewData->unique_indentificator);
        /*if(gettype($uniqueIdentificator->value) !== "string"){
            $uniqueIdentificator->value=strval($uniqueIdentificator->value);
        }*/

        $clientNew = new ClientNew([
            "form_id" => $clientNewData->form_id,
            "information_data" => json_encode($informationDataClient),
            "unique_indentificator" => json_encode($uniqueIdentificator),
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
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param  int  $clietId
     * @return \Illuminate\Http\Response
     */
    public function show($clietId)
    {
        $client = ClientNew::find($clietId);
        $uniqueIndentificator = json_decode($client->unique_indentificator);

        $client->unique_indentificator = (object) [
            'label' => $uniqueIndentificator->label,
            'value' => $uniqueIndentificator->value
        ];

        $client->tags = $client->tags()->get(['tags.id', 'tags.name'])->makeHidden('pivot');
        $client->field_data = $client->customFieldData->field_data ?? [];
        $formAnswer = $client->formanswer()->latest()->first() ?? json_decode($client->directory->data ?? '[]');
        
        $sections = $client->form->section()->get(['name_section', 'fields'])
        ->map(function ($section) use ($formAnswer) {
            $fields = json_decode($section->fields);

            foreach ($fields as $key => $field) {
                foreach ($formAnswer as $answer) {
                    if ($field->id == $answer->id) {
                        $fields[$key]->value = $answer->value;
                    }
                }
            }

            $section->fields = $fields;
            return $section;
        });
        
        $customFields = $client->form->cutomFields->fields ?? [];

        foreach($customFields as $key => $customField) {
            $found = false;
            foreach ($client->field_data as $data) {
                if ($data->id == $customField->id) {
                    $found = true;
                    $customFields[$key]->value = $data->value;
                }
            }

            if (!$found) {
                unset($customFields[$key]);
            }
        }

        $client = $client->only('tags', 'id', 'unique_indentificator');

        return response()->json(['client' => $client, 'custom_fields' => $customFields, 'sections' => $sections]);
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
        $informationDataClient=[];
        $informationData = json_decode($request->information_data);
        foreach($informationData as $data){
            /*if(gettype($data->value)!=="string"){
                $data->value=strval($data->value);
            }*/
            array_push($informationDataClient, (Object)
            [
                "id"=> $data->id,
                "value"=> $data->value,
            ]);
        }

        $clientNew->information_data = $informationDataClient;
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

    /**
     * @desc Funcion para extraer la data de un cliente de las formAnswers almacenadas y puede crear o actualizar el cliente ya existente
     * @param form_id Integer Required: Id del formulario
     * @param form_answer Objeto Required: Form answer
     * @return \Illuminate\Http\Response Objeto con los datos almacenados del cliente
     */
    public function getClientInfoFromFormAnswers($form_id,$form_answers){
        $formController = new FormController();
        $clientNewFields = $formController->getDataClientInForm($form_id);
        foreach($clientNewFields['clientData'] as $clientNewInformation){
            foreach($form_answers as $formAnswer){
                if($clientNewInformation->id == $formAnswer["id"]){
                    $clientNewInformation->value=$formAnswer["value"];
                }
                if($clientNewFields['fields_client_unique_identificator']->id == $formAnswer["id"]){
                    $clientNewFields['fields_client_unique_identificator']->value = $formAnswer["value"];
                }
            }
        }

        $clientNew = new Request();
        $clientNew->replace([
            "form_id" => $form_id,
            "information_data" => json_encode($clientNewFields['clientData']),
            "unique_indentificator" => json_encode($clientNewFields['fields_client_unique_identificator'])
        ]);
        return $this->create($clientNew);
    }


    /**
     * @desc Funcion que devuelve toda la data del cliente identificado con el id que envian
     * @param id Integer Required: Id del cliemte a consultar
     * @return \Illuminate\Http\Response Objeto con los datos almacenados del cliente {"first_name":"firstName","first_lastname":"lastName","document":"1234567890"}
     */
    public function getClient(Request $request){
        $validator = Validator::make($request->all(),[
            'clientId' => 'required|integer',
            'asuntoId' => 'integer'
        ]);
        if($validator->fails())
        {
            return $validator->errors()->all();
        }else{
            $informationCliente = Escalation :: select('information_client')->where('asunto_id',$request->asuntoId)->first();
            $client = ClientNew::select('information_data')->where('id',$request->clientId)->first();
            $clientInformation = json_decode($client->information_data);
            $informationClientNeed=json_decode($informationCliente->information_client);
            $newClientInformation=(Object)[];
            foreach($informationClientNeed as $attClient){
                $name=$attClient->name;
                foreach($clientInformation as $client){
                    if(!isset($attClient->id)){
                        $newClientInformation->$name="";
                    }elseif($attClient->id == $client->id){
                        $newClientInformation->$name=$client->value;
                    }
                }
            }
            return json_encode($newClientInformation);
        }
    }

}
