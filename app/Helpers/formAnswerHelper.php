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
        $sectionsFind   = Section::where('form_id', $formId)->get();
        $arraySections  = $this->getKeysValues($sectionsFind);
        $row            = array();
        $result         = [];

        $i = 0;
        foreach ($arraySections as $obj) {
            $register   = $obj;

            foreach ($register as $key => $value) {
                if (isset($responseSection[$i][$key]) != null || isset($responseSection[$i][$key]) != '') {
                    $row[$key] = trim($responseSection[$i][$key]);
                } else {
                    $row[$key] = NULL;
                }
            }

            array_push($result, $row);
            $row = array();
            $i++;
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