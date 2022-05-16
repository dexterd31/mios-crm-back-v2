<?php

namespace App\Traits;

use App\Models\Section;

trait FieldsForSection
{
    /**
     * @desc Busca los fields por su id en las secciones
     * @param array $search Arreglo de objetos, cada objeto debe contener los elementos id: numero del field al que pertenece
     * @param integer $formId Numero entero con el id del formulario al que se le debe realizar la busqueda de fields
     * @return array arreglo con los field solicitados con toda su estructura
     * @author Leonardo Giraldo Quintero
     */
    public function getSpecificFieldForSection($searchIdFileds , $formId){
        $completeFileds=[];
        $sections = $this->getSections($formId);
        if(count($sections)>0){
            foreach($sections as $section){
                foreach(json_decode($section->fields) as $field){
                    foreach($searchIdFileds as $search){
                        if($search->id==$field->id){
                            $completeFileds[$field->id]=$field;
                        }
                    }
                }
            }
            return $completeFileds;
        }
    }

    /**
     * @desc Función para devolver las secciones de un formulario
     * @param Integer $formId id del formulario que se necesitan traer las secciones
     * @return Array Arreglo de objetos en donde se encuntran todas las secciones del formulario
     * @author Leonardo Giraldo Quintero
     *  */
    public function getSections($formId){
        if(isset($formId)){
            return Section::where('form_id','=',$formId)->get();
        }else{
            return "Error al definir la variable formId";
        }

    }
}