<?php

namespace Helpers;

use App\Models\Section;
use App\Models\ApiQuestion;
use Helpers\MiosHelper;


class FormAnswerHelper
{

    // Funcion para hacer formatear el structureAnswer
    function structureAnswer($formId, $responseSection)
    {       
        $miosHelper         = new MiosHelper();
        $sectionsFind   = Section::where('form_id', $formId)->get();
        $arraySections  = $this->getKeysValues($sectionsFind);
        $row            = array(); // Array para construir el objeto
        $result         = []; // Array para guardar los objectos
        $arrayTemporal  = [];
        $ids            = []; //Array para guardar los ids de las secciones
        // Se obtienen los registro de fields
        foreach ($sectionsFind as $section) {
            array_push($arrayTemporal, $section["fields"]);
        }
        // Se obtines los ids de las secciones
        foreach ($arrayTemporal as $temp => $t) {
            $register   = $miosHelper->jsonDecodeResponse($t);
            foreach ($register as $reg => $r) {
                array_push($ids, $register[$reg]['id'] );
            }
        }

        $i = 0;
        $j = 0;
        foreach ($arraySections as $obj) {
            $register   = $obj;
            
            foreach ($register as $key => $value) {
                if (isset($responseSection[$i][$key]) != null || isset($responseSection[$i][$key]) != '') {
                    $row['id'] = $ids[$j];
                    $row['key'] = $key;
                    $row['value'] = trim($responseSection[$i][$key]);
                    array_push($result, $row);
                } else {
                    $row['id'] = $ids[$j];
                    $row['key'] = $key;
                    $row['value'] = '';
                }
                $j ++;
            }
            $i++;
            $row = array();
        }

        return $result;
    }

    // Funcion para obtener un array con las key values de un formulario
    function getKeysValues($sections)
    {   
        $miosHelper         = new MiosHelper();
        $arraySections      = array();
        $arrayTemporal      = [];
        $arrayFormAnswer    = [];

        // Se obtienen los registro de fields
        foreach ($sections as $section) {
            array_push($arrayTemporal, $section["fields"]);
        }

        // Se construye el array con los keyvalues
        foreach ($arrayTemporal as $obj) {
            $register   = $miosHelper->jsonDecodeResponse($obj);
            $count      = count($register);
            for ($i = 0; $i < $count; $i++) {
                $arraySections[$register[$i]['key']] = NULL;
            }
            array_push($arrayFormAnswer, $arraySections);
            $arraySections = array();
        }
        return $arrayFormAnswer;
    }

    // Funcion que recibe los label de un excel y retorna los keyvalues de un formulario
    function getKeysValuesForExcel($labels, $formId) {
        $miosHelper     = new MiosHelper();
        $sectionsFind   = Section::where('form_id', $formId)->get();
        $arraySections  = array();
        $arrayTemporal  = [];
        $arrayKeyValues = [];

        // Se obtienen los registro de fields
        foreach ($sectionsFind as $section) {
            array_push($arrayTemporal, $section["fields"]);
        }
        // Se buscan los labels para traer los keyvalues
        foreach ($arrayTemporal as $obj) {
            $register   = $miosHelper->jsonDecodeResponse($obj);
            $count      = count($register);
         
            for ($i = 0; $i < $count; $i++) {
                
                if (trim($labels[$i]) == 'Tipo de documento') {
                    $arraySections['document_type_id'] = NULL;
                } else {
                   if(trim($labels[$i]) == trim($register[$i]['label'])){
                    $arraySections[$register[$i]['key']] = NULL;
                   }
                }
                
                $arraySections[$register[$i]['key']] = NULL;
            }
            array_push($arrayKeyValues, $arraySections);
            $arraySections = array();
        }
        return $arrayKeyValues;
    }
}