<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;
use Helpers\MiosHelper;
use Illuminate\Support\Facades\DB;

class ParameterController extends Controller
{
    public function saveParameters(Request $request, $id)
    {
        /* try
        { */  
            $idSuperior = 0;
            foreach($request->data as $dependency)
            {
                $dependency['key'] = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'],$dependency['label']);
                $dependency['key'] =  strtolower( str_replace(' ','-',$dependency['label']) );
                $var = $dependency['key'];
                
                if(!isset($dependency['father']))
                {
                    $father = new Parameter([
                        'form_id' => $id,
                        'name' => $dependency['label'],
                        'key' => $var,
                        'options' => json_encode($dependency['options']),
                        'idSuperior' => null,
                        'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $father->save();     
                    $idSuperior = $father->id;

                }else{

                    $dependences = new Parameter([
                    'form_id' => $id,
                    'label' => $dependency['label'],
                    'options' => json_encode($dependency['options']),
                    'key' => $var,
                    'idSuperior' => $idSuperior,
                    'father' => $dependency['father'],
                    'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $dependences->save();      
                }
            }
        
        return $this->successResponse('Guardado');
    
        /* }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar',500);
        } */
    }

    public function searchParameter($id){
        $parameters = Parameter::where('form_id',$id)->get();
        for($i=0; $i<count($parameters); $i++){
            $parameters[$i]->options = json_decode($parameters[$i]->options);
        } 
        return $parameters;

    }

    public function updateParameters(Request $request, MiosHelper $miosHelper, $id)
    {
      $json_body = $request->getContent();
      $var = json_decode($json_body);
        foreach($var as $dependency)
        {
            $idSuperior = 0;
           
            if(!isset($dependency['father']))
            {
                $parameters = Parameter::where('id',$dependency['id'])->first();
                $parameters->label = $dependency['label'];
                $parameters->options = json_encode($dependency['options']);
                $parameters->father = $dependency['father'];
                $parameters->save();
                $idSuperior = $parameters->id;
            }else{
                $dependencies = Parameter::where('id',$dependency['id'])->first();
                if($dependencies){
                    $dependencies->label = $dependency['label'];
                    $dependencies->options = json_encode($dependency['options']);
                    $dependencies->father = $dependency['father'];
                    $dependencies->save();
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
        }
        return 'ok';
    }
}
