<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;
use Illuminate\Support\Facades\DB;

class ParameterController extends Controller
{
    public function saveParameters(Request $request, $id)
    {
        try
        {  
            $idSuperior = 0;
            foreach($request->data as $dependency)
            {

                if(!isset($dependency['father']))
                {
                    $father = new Parameter([
                        'form_id' => $id,
                        'name' => $dependency['label'],
                        'options' => json_encode($dependency['options']),
                        'idSuperior' => null,
                        'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $father->save();     
                    $idSuperior = $father->id;

                }else{

                    $dependences = new Parameter([
                    'form_id' => $id,
                    'name' => $dependency['label'],
                    'options' => json_encode($dependency['options']),
                    'idSuperior' => $idSuperior,
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

    public function searchParameter($id){
        $parameters = Parameter::where('form_id',$id)->get();
        for($i=0; $i<count($parameters); $i++){
            $parameters[$i]->options = json_decode($parameters[$i]->options);
        } 
        return $parameters;
    }

    public function updateParameters(Request $request, $id){
       
        
        foreach($request->data as $dependency)
        {
            
           
            if(!isset($dependency['father']))
            {
                $parameters = Parameter::where('id',$id)->first();
                $parameters->name = $dependency['label'];
                $parameters->options = json_encode($dependency['options']);
                $parameters->dependency = $dependency['father'];
                $parameters->save();

            }else{
                $dependencies = Parameter::where('form_id',$id)->get();
                $dependencies->name = $dependency->label;
                $dependencies->options = json_encode($dependency->options);
                $dependencies->dependency = $dependency->father;
            }
        }

    }
}
