<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\DocumentType;
use Helpers\MiosHelper;
use Validator;
use Illuminate\Support\Arr;

class ClientController extends Controller
{
    public function getClient($id){
        $client = Client::where('id',$id)->first();
        return $client;
    }

     /**
     * @author Jhon Bernal
     * Método para crear clientes
     * @param $request
     * @return mixed
     */
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

                $success = true;
                $code = 200;
                $keyMessage = 'client'; 
                $data = $client;
            }else{
                $success = false;
                $code = 424;
                $keyMessage = 'No se puede guardar'; 
                $data = 'El cliente con el '. $request->document .' ya se encuentra en el sistema';
            }
        }else{
            $success = false;
            $code = 424;
            $keyMessage = 'No se puede guardar'; 
            $data = 'No se encuentra el document_type en la base de datos';
        }
        return $miosHelper->jsonResponse($success, $code, $keyMessage, $data);
    }

    private function verifyDocumenttype($idDocument){
        $documentType=DocumentType::where('id',$idDocument)->first();
        if(!$documentType){
            return false;
        }
        return true;

    }



     /**
     * @author Jhon Bernal
     * Método para actualizar clientes
     * @param $request
     * @return mixed
     */
    public function update(Request $request, MiosHelper $miosHelper){

        $success = true;
        $validator = Validator::make($request->all(),  
            array(
                'first_name' => 'required',
                'middle_name' => 'required',
                'first_lastname' => 'required',
                'second_lastname' => 'required',
                'document_type_id' => 'required',
                'document' => 'required',
                'phone' => 'required',
                'email' => 'required'
            ),
            array(
                'required' => 'El parametro :attribute es requerido.',
                'unique' => 'El valor :input ya existe  .'
            )
        );
        if ($validator->fails()) {
            $success = false;
            $code = 424;
            $keyMessage = 'message';
            $data = Arr::collapse($validator->errors()->messages());

        }
   
        try {
            if ($success) {
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

                $success = true;
                $code = 200;
                $keyMessage = 'client'; 
                $data = $client;
            }
        } catch (\Throwable $th) {
            $success = false;
            $code = 424;
            $keyMessage = 'message'; 
            $data = $th->getMessage();
            
        }
        return $miosHelper->jsonResponse($success, $code, $keyMessage, $data);
    }

    /**
     * @author Jhon Bernal
     * Método para un cliente consulta o todos
     * @param $document
     * @return mixed
     */
    public function list($document, MiosHelper $miosHelper){
        $client = Client::where('document',$document)->first();
        if (!$client) {
            $client = Client::all();
        }
        return $miosHelper->jsonResponse(true,200,'search',$client);
    }

     /**
     * @author Jhon Bernal
     * Método para un cliente buscar
     * @param $value
     * @param $type
     * @return mixed
     */
    public function search(Request $request, MiosHelper $miosHelper){
        $value = $request->value;
        $type = $request->type;
        $success = true;
        $validator = Validator::make(array('value' => $value,'type' => $type),  
            array(
                'value' => 'required',
                'type' => 'required',
            ),
            array(
                'required' => 'El parametro :attribute es requerido.'
            )
        );
        if ($validator->fails()) {
            $success = false;
            $code = 424;
            $keyMessage = 'message';
            $data = Arr::collapse($validator->errors()->messages());

        }

        if ($success) {    
            $client = Client::where($type,$value)->first();
            $success = true;
            $code = 200;
            $keyMessage = 'client'; 
            $data = $client;
        }
        return $miosHelper->jsonResponse($success, $code, $keyMessage, $data);
    }
}
