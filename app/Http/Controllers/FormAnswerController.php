<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
use App\Models\Client;
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
            $client = new Client([
               /*  'name_client' => $json_body->client->name_client,
                'lastname' => $json_body->client->lastname, */
                'unique_id' => $json_body->client->unique_id,
               /*  'email' => $json_body->client->email,
                'phone' => $json_body->client->phone, */
                'basic_information' => json_encode($json_body->client->basic_information)
            ]);
            $client->save(); 
            $form_answer = new FormAnswer([
                'user_id' => 1,
                'channel_id' => 1,
                'client_id' => $client->id,
                'form_id' => $json_body->form_id,
                'structure_answer' => json_encode($json_body->sections)
            ]);
            $form_answer->save();
            return 'guardado';
            return $this->successResponse('Guardado Correctamente');
    
         }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar el formulario',500);
        }      
    }

    /**
     * Nicol Ramirez
     * 19-02-2021
     * Método para editar la información de la gestión del formulario
     */
    public function editInfo(Request $request, $id){
        $form_answer = FormAnswer::find($id);
        $form_answer->structure_answer = json_encode($request->answer);
        dd($form_answer);
        $form_answer->save();
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
                      ->where('clients.document','like','%'.$request->ident_id.'%')
                      ->where('clients.email','like','%'.$request->phone.'%')
                      ->where('clients.phone','like','%'.$request->email.'%')
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
