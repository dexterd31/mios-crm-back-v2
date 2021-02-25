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
            return 'guardado';

        }else{
            $client = Client::find($json_body->client_id)->first();
            $formanswer = FormAnswer::where( 'client_id', $json_body->client_id)->first();
            $formanswer->structure_answer = json_encode($json_body->sections);
            $formanswer->save();

            foreach($json_body->sections as $section)
            {
                if(!empty($section->document_type_id)){

                    $client->document_type_id = $section->document_type_id;
                    $client->first_name = $section->firstName;
                    $client->middle_name = $section->middleName;
                    $client->first_lastname = $section->lastName;
                    $client->second_lastname = $section->secondLastName;
                    $client->document = $section->document;
                    $client->save();
                    
                }/* else{
                    foreach($section as $key => $value){
                        $sect->form_id = $json_body->form_id;
                        $sect->client_id = $client->id;
                        $sect->key = $key;
                        $sect->value = $value;
                        
    
                        $sect->save();
                      
                    }
                }
            }

            return 'editado';
        } */
     
    }

      /**
     * Nicol Ramirez
     * 17-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request)
    {
        $filter = DB::table('form_answers')
                      ->join('clients','form_answers.client_id','=','clients.id')
                      ->where('clients.document','like','%123456789%')
                      ->get();

        return $filter;
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
