<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\DocumentType;
use Helpers\MiosHelper;

class ClientController extends Controller
{
    public function getClient($id){
        $client = Client::where('id',$id)->first();
        return $client;
    }

    public function store(Request $request, MiosHelper $miosHelper){
        if($this->verifyDocumenttype($request->document_type_id)){
            $client = Client::where('document', $request->document)->first();
            if (!$client) {
                $client=new Client([
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'first_lastname' => $request->first_lastname,
                    'second_lastname' => $request->second_lastname,
                    'document_type_id' => $request->document_type_id,
                    'document' => $request->document,
                    'phone' => $request->phone,
                    'email' => $request->email
                ]);
                $client->save();
                $client->action="created";
            } 
            return $miosHelper->jsonResponse(true,200,'client',$client);
        }else{
            return $miosHelper->jsonResponse(false,424,'client','No se encuentra el document_type en la base de datos');
        }
    }

    private function verifyDocumenttype($idDocument){
        $documentType=DocumentType::where('id',$idDocument)->first();
        if(!$documentType){
            return false;
        }
        return true;

    }

    /**
     * Jhon Bernal
     * 14/07/21
     * Método para actualizar clientes
     */
    public function update(Request $request, MiosHelper $miosHelper){
        try {
            $client = Client::where('document',$request->document)->first();
            $client->first_name = $request->first_name;
            $client->middle_name = $request->middle_name;
            $client->first_lastname = $request->first_lastname;
            $client->second_lastname = $request->second_lastname;
            $client->document_type_id = $request->document_type_id;
            $client->document = $request->document;
            $client->phone = $request->phone;
            $client->email = $request->email;
            $client->save();
            return $miosHelper->jsonResponse(true,200,'actualizado',$client);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(),500);
        }
    }


    /**
     * Jhon Bernal
     * 14/07/21
     * Método para un cliente consulta o todos
     */
    public function list(Request $request, MiosHelper $miosHelper){
        if (isset($request->document)) {
            $client = Client::where('document',$request->document)->first();
        }else{
            $client = Client::all();
        }

        return $miosHelper->jsonResponse(true,200,'search',$client);
    }

    public function search(Request $request, MiosHelper $miosHelper){
        $value = $request->value;
        $type = $request->type;

        if (!isset($value)) {
            $data = $this->errorResponse('el campo valor es requerido',500);
        }elseif(!isset($type)){
            $data = $this->errorResponse('el campo tipo es requerido',500);
        }elseif (!isset($value) &&!isset($type)) {
            $data = $this->errorResponse('el campo tipo y valor es requerido',500);
        }
        try {    
            $client = Client::where($type,$value)->first();
            $data = $miosHelper->jsonResponse(true,200,'search',$client);

            return response()->json($data, $data['code']);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage(),500);
        }
    }
}
