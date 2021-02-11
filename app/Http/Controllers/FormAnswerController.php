<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
use App\Models\Section;

class FormAnswerController extends Controller
{
    public function saveinfo(Request $request){
        try{ 
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
}
