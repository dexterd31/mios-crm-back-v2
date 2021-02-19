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
/*         try
        {  */
            $client = new Client([
                'name_client' => $request->input('name_client'),
                'lastname' => $request->input('lastname'),
                'document' => $request->input('document'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'basic_information' => json_encode($request->input('info'))
            ]);
            $client->save(); 
            $form_answer = new FormAnswer([
                'user_id' => 1,
                'channel_id' => 1,
                'client_id' => $client->id,
                'form_id' => $request->input('form_id'),
                'structure_answer' => json_encode($request->input('answer'))
            ]);
            $form_answer->save();

            return 'guardado';
/*             return $this->successResponse('Guardado Correctamente');
    
         }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar el formulario',500);
        }      */
    }

    /**
     * Nicol Ramirez
     * 19-02-2021
     * Método para editar la información de la gestión del formulario
     */
    public function editInfo(Request $request, $id){
        $form_answer = FormAnswer::find($id);
        $form_answer->structure_answer = $request->answer;
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

  
}
