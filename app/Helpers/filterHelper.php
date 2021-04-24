<?php

namespace Helpers;

use App\Models\FormAnswer;
use App\Models\Client;
use App\Models\Directory;
use App\Models\ApiConnection;

class FilterHelper
{

    // Funcion para filtar por gestiones de mios
    function filterByGestions($formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value)
    {
        // Se continua la busqueda por gestiones
        // siempre hay al menos un item de filtro
        $form_answers = FormAnswer::where('form_id', $formId)
            ->whereRaw("json_unquote(json_extract(structure_answer, json_unquote(replace(json_search(structure_answer,'one', '$item1key'), '.key', '.value')))) like '%$item1value%'");
        
        if(!empty($item2key)){
            $form_answers = $form_answers
                ->whereRaw("json_unquote(json_extract(structure_answer, json_unquote(replace(json_search(structure_answer,'one', '$item2key'), '.key', '.value')))) like '%$item2value%'");
        }

        if(!empty($item3key)){
            $form_answers = $form_answers
                ->whereRaw("json_unquote(json_extract(structure_answer, json_unquote(replace(json_search(structure_answer,'one', '$item3key'), '.key', '.value')))) like '%$item3value%'");
        }
            
        $form_answers = $form_answers->with('client')->paginate(10);
        return $form_answers;
    }

    // funcion para obtener el id cliente 
    function searchClient($item1value, $item2value, $item3value)
    {
        $miosHelper = new MiosHelper();
        $clientInfo = Client::Where('document', 'like', '%' . $item1value . '%')
            ->where('document', 'like', '%' . $item2value . '%')
            ->where('document', 'like', '%' . $item3value . '%')->select('id')->first();
        $clientId = $clientInfo != null ? $miosHelper->jsonDecodeResponse($clientInfo->id) : null;
        return $clientId;
    }

    // Funcion para buscar gestion por id del cliente
    function searchGestionByClientId($formId, $clientId)
    {
        $where = ['form_id' => $formId, 'client_id' => $clientId];
        $form_answers = FormAnswer::where($where)->with('client')->paginate(10);
        return $form_answers;
    }

    // Funcion para filtar por base de datos
    function filterByDataBase($formId, $clientId, $item1value, $item2value, $item3value)
    {
        $form_answers = null;
        if ($clientId != null) {
            // Se continua en directory
            $where = ['form_id' => $formId, 'client_id' => $clientId];
            $form_answers = Directory::where($where)->with('client')->paginate(10);
        } else {
            $form_answers = Directory::where('form_id', $formId)
                            ->where('data', 'like', '%' . $item1value . '%')
                            ->where('data', 'like', '%' . $item2value . '%')
                            ->where('data', 'like', '%' . $item3value . '%')
                            ->with('client')->paginate(10);
        }

        return $form_answers;
    }

    // Funcion para buscar por api
    function filterbyApi($formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value){
        // Se busca si la solicitud tiene cargue por api
        $miosHelper = new MiosHelper();
        $apiHelper  = new ApiHelper();
        $where = ['form_id' => $formId, 'request_type' => 2, 'status' => 1];
        $apiFind = ApiConnection::where($where)->first();
        $parameter  = null;
        $form_answers = null;
        if ($apiFind) {
            // Se busca los item de busqueda 
            if ($apiFind['parameter'] != null || $apiFind['parameter'] != '') {
                if ($item1key == $apiFind['parameter']) {
                    $parameter = $item1value;
                } else if ($item2key == $apiFind['parameter']) {
                    $parameter = $item2value;
                } else if ($item3key == $apiFind['parameter']) {
                    $parameter = $item3value;
                }
            }

            // Se hace el cargue de la información con la api registrada.
            $infoApi = $apiHelper->getInfoByApi($apiFind, $parameter, $formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value );

            $form_answers = $infoApi;
          
            if($form_answers != null) {
                $answerApi = [];
                array_push($answerApi, $form_answers);
                $form_answers = $miosHelper->paginate($answerApi, $perPage = 15, $page = null);
            }

        }
        return $form_answers;
    } 
}
