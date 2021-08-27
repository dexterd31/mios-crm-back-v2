<?php

namespace App\Http\Controllers;

use App\Models\ClientNew;
use Illuminate\Http\Request;
use Helper\MiosHelper;

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
			$this->setClientNewModel(new ClientNewModel());
		}
		return $this->clientNewModel;
	}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, MiosHelper $miosHelper)
    {
        $validator = Validator::make($request->all(),[
            'form_id' => 'required|integer',
        ]);

        if($validator->fails())
        {
            return $validator->errors()->all();
        }

        $this->getClientNew();
        $clients = $this->clientNew->where($$request->formId, "form_id");
        if($request->uniqueIndentificator)
        {
            $clients = $clients->where($request->uniqueIndentificator, "unique_indentificator");
        }
        return $clients->get();
    }

    // Descripción: Función que recibe un objeto y realiza las validaciones y arreglos a
    // la data para pasar a la función saveClient para que sean almacenados en la tabla clients_new 
    // Parámetros:
    // Array Datos de uno o varios clientes en un objeto 
    // Retorna: Objeto con un parámetro estado el cual será true or false dependiendo del resultado 
    // del proceso, y un parámetro data con el objeto de  creación del cliente ósea los datos almacenados en la tabla clients_new. 
    public function create(Request $request, MiosHelper $miosHelper)
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
            $clientNew = $this->index($request->unique_indentificator, $request->form_id);
            if(isset($clientNew->id))
            {
               $data = $this->update($request, $clientNew);
            }
    
            $data = $this->save($request);
        }
    }

    private function save($clientNewData)
    {
        $this->getClientNew();
        $this->clientNew->insert([$clientNewData]);
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
