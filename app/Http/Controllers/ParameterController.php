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
        
        return $this->successResponse('Guardado');
    
        /* }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar',500);
        } */
    }

    public function searchParameterByFather($id,$father){
        
        $parameters = Parameter::where('form_id',$id)
        ->where('father',$father)->get();
        for($i=0; $i<count($parameters); $i++){
            $parameters[$i]->options = json_decode($parameters[$i]->options);
        } 
        return $parameters;

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
        $json_body = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()), true);
        $dataInfo = $json_body['data'];
        $idSuperior = null;
        foreach($dataInfo as $dependency)
        {
            $parameters = Parameter::where('id',$dependency['id'])->first();

            if (!empty($parameters) && is_object($parameters)) {
                $parameters->name = $dependency['label'];
                $parameters->options = json_encode($dependency['options']);
                $parameters->dependency = isset($dependency['father']) ? $dependency['father'] : null ;
                $parameters->idSuperior = $dependency['idSuperior'];
                $parameters->have_dependencies = $dependency['have_dependencies'];
                $parameters->save();
                if($parameters->have_dependencies == 1){
                    $idSuperior = $parameters->id;
                }
                
                
            } else {
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
        }
        return 'ok';
    }
}
