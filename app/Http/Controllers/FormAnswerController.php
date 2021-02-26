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
     * Olme Marin
     * 26-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request)
    {   
        $json_body = json_decode($request->getContent());
  
        $formId     = $json_body->form_id;

        $item1Key   = $json_body->item1_key;
        $item1value = $json_body->item1_value;

        $item2Key   = $json_body->item2_key;
        $item2value = $json_body->item2_value;

        $item3Key   = $json_body->item3_key;
        $item3value = $json_body->item3_value;

        $registers = [];
        $form_answers = FormAnswer::where('form_id', $formId )->get();

        foreach ($form_answers as $form){
            $array =  json_decode( json_encode( $form->structure_answer, true ));
            $arr =     json_decode($array,TRUE);   
            $data = [];
            foreach($arr as $a){
                $find = false;
                $find2 = false;
                $find3 = false;
                if (isset($item1value) && strlen($item1value) > 0) {
                    $find = array_search($item1value, $a);
                }                    
                if (isset($item2value) && strlen($item2value) > 0) {
                    $find2 = array_search($item2value, $a);
                } 
                if (isset($item3value) && strlen($item3value) > 0) {
                    $find3 = array_search($item3value, $a);
                } 

                if ($find || $find2 || $find3) {
                    array_push($registers, $arr);              
                }
            }
        }
        return [

            'suceess' => true,
            
            'result' => $registers
            
            ];
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
