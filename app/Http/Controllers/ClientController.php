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
            eturn $miosHelper->jsonResponse(false,424,$th->getMessage());
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



    /**
     * Jhon Bernal
     * 14/07/21
     * Método para un cliente buscar
     */
    public function search(Request $request, MiosHelper $miosHelper){
        $value = $request->value;
        $type = $request->type;
        $resultValue = false;

        if (!isset($value)) {
            $data = 'el campo valor es requerido';
            $resultValue = true;
        }elseif(!isset($type)){
            $data = 'el campo tipo es requerido';
            $resultValue = true;
        }elseif (!isset($value) &&!isset($type)) {
            $data = 'el campo tipo y valor es requerido';
            $resultValue = true;
        }
        if ($resultValue) {
            return $miosHelper->jsonResponse(false,424,$resultValue);
        }
        try {    
            $client = Client::where($type,$value)->first();
            return $miosHelper->jsonResponse(true,200,'search',$client);
        } catch (\Throwable $th) {
            return $miosHelper->jsonResponse(false,424,$th->getMessage());
        }
    }
}
