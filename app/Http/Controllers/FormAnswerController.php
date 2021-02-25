<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
use App\Models\Client;
use App\Models\KeyValue;
use Illuminate\Support\Facades\DB;

class FormAnswerController extends Controller
{
      /**
     * Nicol Ramirez
     * 11-02-2020
     * Método para guardar la información del formulario
     */
    public function saveinfo(Request $request)
    {
        try
        {
            $json_body = json_decode($request->getContent());
            $client = null; 
            if($json_body->client_id == null){
            
                foreach($json_body->sections as $section)
                {
                    if(!empty($section->document_type_id)){
                        $client = new Client([
                            'document_type_id' => $section->document_type_id,
                            'first_name' => $section->firstName,
                            'middle_name' => $section->middleName,
                            'first_lastname' => $section->lastName,
                            'second_lastname' => $section->secondLastName,
                            'document' => $section->document
                        ]);
                        $client->save();
                    }else{
                    
                        foreach($section as $key => $value){
                            $sect = new KeyValue([
                                'form_id' => $json_body->form_id,
                                'client_id' => $client->id,
                                'key' => $key,
                                'value' => $value,
                                'description' => 0
                            ]);
                            $sect->save();
                        }
                    }
                }
                $form_answer = new FormAnswer([
                    'user_id' => 1,
                    'channel_id' => 1,
                    'client_id' => $client->id,
                    'form_id' => $json_body->form_id,
                    'structure_answer' => json_encode($json_body->sections)
                ]);
                $form_answer->save();
                $message = 'Informacion guardada correctamente';
                
                
            }else{
                $message = '';
                $client  = Client::find($json_body->client_id)->first();
                $sect    = KeyValue::where('client_id', $json_body->client_id)->get();
                if ($sect != null) {                      
                    foreach($json_body->sections as $section)
                    {   
                        foreach($section as $key => $value){
                            $where = [
                                'client_id' => $json_body->client_id,
                                'form_id' => $json_body->form_id,
                                'key' => $key
                            ];
                            $keyValue = KeyValue::where($where)->first();
                            if (!empty($keyValue) && is_object($keyValue)) {
                                $keyValue->key = $key;
                                $keyValue->value = $value;
                                $keyValue->save();
                            }
                        }
                        
                        if(!empty($section->document_type_id)){
                            $client->document_type_id = $section->document_type_id;
                            $client->first_name = $section->firstName;
                            $client->middle_name = $section->middleName;
                            $client->first_lastname = $section->lastName;
                            $client->second_lastname = $section->secondLastName;
                            $client->document = $section->document;
                            $client->save();
                        }
                    }
                    $form_answer = new FormAnswer([
                        'user_id' => 1,
                        'channel_id' => 1,
                        'client_id' => $client->id,
                        'form_id' => $json_body->form_id,
                        'structure_answer' => json_encode($json_body->sections),
                    ]);
                    $form_answer->save();
                    $message = 'Informacion actualizada correctamente';
                } else {
                    foreach($json_body->sections as $section){
                        foreach($section as $key => $value){
                            $sect = new KeyValue([
                                'form_id' => $json_body->form_id,
                                'client_id' => $client->id,
                                'key' => $key,
                                'value' => $value,
                                'description' => 0
                            ]);
                            $sect->save();
                        }
                    }
                    
                    $form_answer = new FormAnswer([
                        'user_id' => 1,
                        'channel_id' => 1,
                        'client_id' => $client->id,
                        'form_id' => $json_body->form_id,
                        'structure_answer' => json_encode($json_body->sections),
                    ]);
                    $form_answer->save();
                    $message = 'Informacion creada y actualizada correctamente';
                }
            
            }
            return $this->successResponse($message);
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar el formulario',500);
        }
}

      /**
     * Nicol Ramirez
     * 17-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request)
    {
        $form_answer = FormAnswer::with('client')
        ->select('structure_answer')->first();
        $form_answer->structure_answer = json_decode($form_answer->structure_answer);

        return $form_answer;
    }

     /**
     * Nicoll Ramirez
     * 22-02-2021
     * Método para consultar el tipo de documento en las respuestas del formulario
     */
    public function searchDocumentType(){
        $documentType = DB::table('document_types')
        ->select('id','name_type_document')->get();

        return $documentType;
    }
  
}
