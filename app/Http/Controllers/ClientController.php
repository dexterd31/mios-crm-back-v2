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
            } else {
                $client->first_name = $request->first_name;
                $client->middle_name = $request->middle_name;
                $client->first_lastname = $request->first_lastname;
                $client->second_lastname = $request->second_lastname;
                $client->document_type_id = $request->document_type_id;
                $client->document = $request->document;
                $client->phone = $request->phone;
                $client->email = $request->email;
                $client->save();
                $client->action="updated";
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
}
