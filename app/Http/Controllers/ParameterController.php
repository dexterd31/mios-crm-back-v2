<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parameter;
use Helpers\MiosHelper;
use Illuminate\Support\Facades\DB;

class ParameterController extends Controller
{
    /**
     * Nicoll Ramirez
     * 19-03-2021
     * Método para crear las dependencias en el formulario
     */
    public function saveParameters(Request $request, $id)
    {
         try
        { 
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
                        'label' => $dependency['label'],
                        'key' => $var,
                        'idSuperior' => null,
                        'father' => null,
                        'have_dependencies' => $dependency['have_dependencies'],
                        'options' => json_encode($dependency['options'])
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
        
        return $this->successResponse('Dependencia Guardada Correctamente');
    
         }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar las dependencias',500);
        }
    }
/**
 * Nicoll Ramirez
 * 19-03-2021
 * Método para consultar las dependencias por padre
 */
    public function searchParameterByFather($id,$father){
        
        $parameters = Parameter::where('form_id',$id)
        ->where('father',$father)->get();
        for($i=0; $i<count($parameters); $i++){
            $parameters[$i]->options = json_decode($parameters[$i]->options);
        } 
        return $parameters;

    }
    /**
     * Nicoll Ramirez
     * 19-03-2021
     * Método para consultar todas las dependencias existentes en el formulario
     */
    public function searchParameter($id){
        
        $parameters = Parameter::where('form_id',$id)->get();
        for($i=0; $i<count($parameters); $i++){
            $parameters[$i]->options = json_decode($parameters[$i]->options);
        } 
        return $parameters;

    }
/**
 * Nicoll Ramirez
 * 22-03-2021
 * Método para editar las dependencias
 */
    public function updateParameters(Request $request, MiosHelper $miosHelper, $id)
    {
       /*  try{ */

        
        $json_body = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()), true);
        $dataInfo = $json_body['data'];
        $idSuperior = null;
        foreach($dataInfo as $dependency)
        {
            $dependency['key'] = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'],$dependency['label']);
            $dependency['key'] =  strtolower( str_replace(' ','-',$dependency['label']) );
            $var = $dependency['key'];
            $parameters = Parameter::where('id',$dependency['id'])->first();
            if (!empty($parameters) && is_object($parameters)) {
                $parameters->label = $dependency['label'];
                $parameters->key = $var;
                $parameters->options = json_encode($dependency['options']);
                $parameters->father = isset($dependency['father']) ? $dependency['father'] : null ;
                $parameters->idSuperior = $dependency['idSuperior'];
                $parameters->have_dependencies = $dependency['have_dependencies'];
                $parameters->save();
                if($parameters->have_dependencies != 1){
                    $idSuperior = $parameters->id;
                }
                
                
            } else {
                if(!isset($dependency['father']))
                {
                    $father = new Parameter([
                        'form_id' => $id,
                        'label' => $dependency['label'],
                        'key' => $var,
                        'options' => json_encode($dependency['options']),
                        'idSuperior' => null,
                        'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $father->save();     

                }else{
                   
                    $dependences = new Parameter([
                    'form_id' => $id,
                    'label' => $dependency['label'],
                    'key' => $var,
                    'options' => json_encode($dependency['options']),
                    'idSuperior' => $idSuperior,
                    'father' => $dependency['father'],
                    'have_dependencies' => $dependency['have_dependencies']
                    ]); 
                    $dependences->save();      
                }
                

            }
        }
        return $this->successResponse('Dependencias Modificadas Correctamente');
    /* 
         }catch(\Throwable $e){
            return $this->errorResponse('Error al modificar las dependencias',500);
        } */
    }
}
