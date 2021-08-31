<?php

namespace Helpers;

use App\Models\FormAnswer;
use App\Models\Client;
use App\Models\Directory;
use App\Models\ApiConnection;
use PhpParser\Node\Stmt\Foreach_;

class FilterHelper
{

    // Funcion para filtar por gestiones de mios
    function filterByGestions($formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value)
    {
        // Se continua la busqueda por gestiones
        // siempre hay al menos un item de filtro
        //dd("json_contains(structure_answer, '{\"key\":\"$item1key\", \"value\":\"$item1value\"}')");
        $form_answers = FormAnswer::where('form_id', $formId)
            ->whereRaw("json_contains(lower(structure_answer), lower('{\"key\":\"$item1key\", \"value\":\"$item1value\"}'))");


        if(!empty($item2key)){
            $form_answers = $form_answers
            ->whereRaw("json_contains(lower(structure_answer), lower('{\"key\":\"$item2key\", \"value\":\"$item2value\"}'))");

        }

        if(!empty($item3key)){
            $form_answers = $form_answers
            ->whereRaw("json_contains(lower(structure_answer), lower('{\"key\":\"$item3key\", \"value\":\"$item3value\"}'))");

        }

        $form_answers = $form_answers->with('client')->paginate(5);
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
        $form_answers = FormAnswer::where($where)->with('client')->paginate(5);
        return $form_answers;
    }

    // Funcion para filtar por base de datos
    function filterByDataBase($formId, $clientId, $filters)
    {
        $formAnswersQuery = new Directory();
        if ($clientId != null) {
            // Se continua en directory
            $where = ['form_id' => $formId, 'client_new_id' => $clientId];
            $formAnswersQuery = $formAnswersQuery->where($where);
        }
        else
        {
            $formAnswersQuery = $formAnswersQuery->where('form_id', $formId);
            foreach ($filters as $filter)
            {
                $formAnswersQuery = $formAnswersQuery->where('data', 'like', '%' . $filter["value"] . '%');
            }
        }
        return $formAnswersQuery->with('clientNew')->paginate(5);
    }

    // Funcion para buscar por api
    function filterbyApi($formId, $filters){
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
                foreach ($filters as $filter)
                {
                    if ($filter["value"] == $apiFind['parameter'])
                    {
                        $parameter = $filter["value"];
                        break;
                    }
                }
            }

            // Se hace el cargue de la información con la api registrada.
            $infoApi = $apiHelper->getInfoByApi($apiFind, $parameter, $formId, $filters);

            $form_answers = $infoApi;

            if($form_answers != null) {
                $answerApi = [];
                array_push($answerApi, $form_answers);
                $form_answers = $miosHelper->paginate($answerApi, $perPage = 5, $page = null);
            }

        }
        return $form_answers;
    }
}
