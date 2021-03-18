<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;

class ParameterController extends Controller
{
    public function saveParameters(Request $request, $id)
    {
        try
        {  
            $id= 0;
            foreach($request->data as $dependency)
            {

                if(!isset($dependency['father']))
                {
                    $father = new Parameter([
                        'form_id' => $id,
                        'name' => $dependency['name'],
                        'options' => json_encode($dependency['options']),
                        'idSuperior' => null,
                        'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $father->save();     
                    $id= $father->id;

                }else{

                    $dependences = new Parameter([
                    'form_id' => $id,
                    'name' => $dependency['name'],
                    'options' => json_encode($dependency['options']),
                    'idSuperior' => $id,
                    'dependency' => $dependency['father'],
                    'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $dependences->save();      
                }
            }
        
        return $this->successResponse('Guardado');
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar',500);
        }
    }
}
