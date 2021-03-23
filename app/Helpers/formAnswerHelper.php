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

    // Funcion para cargar la información desde una api registrada
    function getInfoByApi($registerApi, $parameter, $formId) {
        $miosHelper                 = new MiosHelper();
        $mode                       = $registerApi['mode'];
        $url                        = $registerApi['url'];
        $autorization_type          = $registerApi['autorization_type'];
        $token                      = $registerApi['token'];
        $other_autorization_type    = $registerApi['other_autorization_type'];
        $other_token                = $registerApi['other_token'];
        $json_send                  = $registerApi['json_send'];
        $rrhh = [
            'campaign_id' => 0,
            'email' => '',
            'first_name' => '',
            'id_number' => '',
            'id_type' => '',
            'last_name' => '',
            'phone' => ''
        ];
        $userData = ['rrhh' => $rrhh];
        $responseArray  = [
            'created_at' => 0,
            'updated_at' => 0,
            'user_id'    => 0,
            'form_id'    => 0,
            'structure_answer'=>[],
            'channel_id' => 0,
            'client_id'  => 0,
            'userdata'   => $userData,
            'client'     => 0,
        ];

        if ($registerApi['parameter'] != null || $registerApi['parameter'] != '') {
            $url  = $url.'/'.$parameter; 
        }

        // Se obtiene la infromación del api registrado
        $result = $this->httpRequest($mode, $url, $autorization_type, $token, $other_autorization_type, $other_token, $json_send);
        $apiData = $miosHelper->jsonDecodeResponse(json_encode($result));
        
        $num = isset($apiData) ? count($apiData) : 0;
        /**
         * Se hace el match con la respuesta de mios 
         * Se llama la relación del api con el formulario de mios
        */
        $where = ['status' => 1, 'form_id' => $formId, 'api_id' => $registerApi['id']];
        $apiRelationship = ApiQuestion::where($where)->first();

        if ($apiRelationship && $num > 1) {
            $relationship = $miosHelper->jsonDecodeResponse($apiRelationship['relationship']);
            $i = 0;

            // foreach para recorrer cada registro de la realación
            foreach($relationship as $rel) {
                foreach ($rel as $key => $value) {
                    /** 
                     * $key es el nombre de la llave y $value el valor
                     * Se ve si el valor tiene hijos
                    */
                    if ($relationship[$i][$key] != null || $relationship[$i][$key] != '') {
             
                        $valueArray = explode(',', $value);
                   
                        $count      = count($valueArray);
                        $subNivel   = '';
                        if ($count > 1) {
                            for ($j=0; $j < $count; $j++) { 
                                if ($j == 0) {
                                    $subNivel   = $apiData[$valueArray[$j]];
                                } else {
                                    $subNivel   = $subNivel[$valueArray[$j]];
                                }
                            }
                            $relationship[$i][$key] = $subNivel;
                        } else {
                            $relationship[$i][$key] = $apiData[$value];
                        }
                    } else {
                        $relationship[$i][$key] = '';
                    }
                }
                $i++;
            }
            $responseArray['structure_answer'] = $relationship;
            return $responseArray;
        } else {
            return null;
        }
    }

    // Funcion para hacer la consulta http por curl
    function httpRequest($mode, $url, $autorization_type, $token, $other_autorization_type, $other_token, $json_send) {
        $miosHelper     = new MiosHelper();
        if ($mode == 'GET') {
            $headersData = array();

            if( $autorization_type != null || $token != null) {
                $headersData ['Autorization'] =  $autorization_type.' '.$token;   
            }
            if ($other_autorization_type != null && $other_token != null) {
                $headersData[$other_autorization_type] = $other_token;
            }
  
            $headers = $headersData;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($ch);
            curl_close($ch);
            $apiData = $miosHelper->jsonDecodeResponse($result);
            return $apiData;
        } else {

            if( $autorization_type != null || $token != null) {
                $auth  = isset($autorization_type) ? $autorization_type.' '.$token : $token;
                $autorization = "Authorization:$auth";   
            }
            if ($other_autorization_type != null && $other_token != null) {
                $otherAutorization = "$other_autorization_type:$other_token";
            }

            $headers = array("Content-Type:application/json", "$autorization", "$otherAutorization");
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_send);
            $result = curl_exec($ch);

            curl_close($ch);
            $apiData = $miosHelper->jsonDecodeResponse($result);

            return $apiData;
        }
    } 

}
