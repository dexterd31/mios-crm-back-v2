<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
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
            $sections_array = json_decode($request->sections_array); 

            foreach($sections_array as $section){
    
                $formanswer = new FormAnswer();
                $formanswer->section_id = $section->section_id;
                $formanswer->client_id = $section->client_id;
                $formanswer->structure_answer = json_encode($section->structure_answer);
    
                $formanswer->save();
            }
            return $this->successResponse('Guardado Correctamente');
    
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
        $filter = DB::table('form_answers')
                      ->join('clients','form_answers.client_id','=','clients.id')
                      ->where('clients.document','like','%123456789%')
                      ->where('clients.email','like','%nicol@gmail.com%')
                      ->where('clients.phone','like','%12233243%')
                      ->get();

        return $filter;
    }
}
