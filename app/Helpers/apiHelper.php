<?php

namespace Helpers;

use App\Models\Section;
use App\Models\ApiQuestion;
use App\Models\ApiConnection;
use Helpers\MiosHelper;

class ApiHelper
{
    // Funcion para cargar la información desde una api registrada
    function getInfoByApi($registerApi, $parameter, $formId, $item1key, $item1value, $item2key, $item2value, $item3key, $item3value)
    {
        try {
            $miosHelper                 = new MiosHelper();
            $mode                       = $registerApi['mode'];
            $url                        = $registerApi['url'];
            $autorization_type          = $registerApi['autorization_type'];
            $token                      = $registerApi['token'];
            $other_autorization_type    = $registerApi['other_autorization_type'];
            $other_token                = $registerApi['other_token'];
            $json_send                  = $registerApi['json_send'];
            $api_type                   = $registerApi['api_type'];
            $login                      = true; // Bandera para saber si se pudo hacer login en el api

            if ($registerApi['mode'] == 'GET') {
                $url  = $url . '/' . $parameter;
            }

            // Se valida si valida si el registro requiere de login 
            $whereLogin = ['status' => 1, 'form_id' => $formId, 'request_type' => 1];
            $isLogin = ApiConnection::where($whereLogin)->first();
            if (!empty($isLogin)) {
                $login = false;
                // Se hace login en el api
                $login = $this->loginApi($isLogin);
            }

            if ($login) {
                // Se llena las variables de busqueda
                if ($api_type == 1) {
                    // Api rest
                    $variables = [$item1key => $item1value, $item2key => $item2value, $item3key => $item3value];
                    $json_send = $miosHelper->jsonDecodeResponse($json_send);
                    $json_send = $variables;
                    $json_send = json_encode($json_send);
                } else {
                    //Graphql
                    $variables = [$item1key => $item1value, $item2key => $item2value, $item3key => $item3value];
                    $json_send = $miosHelper->jsonDecodeResponse($json_send);
                    $json_send['variables'] = $variables;
                    $json_send = json_encode($json_send);
                }

                // Se obtiene la infromación del api registrado
                $result = $this->httpRequest($mode, $url, $autorization_type, $token, $other_autorization_type, $other_token, $json_send, $api_type);
                $apiData = $miosHelper->jsonDecodeResponse(json_encode($result));

                $num = isset($apiData) ? count($apiData) : 0;

                /**
                 * Se hace el match con la respuesta de mios 
                 * Se llama la relación del api con el formulario de mios
                 */
                $where = ['status' => 1, 'form_id' => $formId, 'api_id' => $registerApi['id']];
                $apiRelationship = ApiQuestion::where($where)->first();

                if ($apiRelationship && $num > 0) {
                    $relationship = $miosHelper->jsonDecodeResponse($apiRelationship['relationship']);

                    $i = 0;
                    // foreach para recorrer cada registro de la realación
                    foreach ($relationship as $rel) {
                        foreach ($rel as $key => $value) {
                            /** 
                             * $key es el nombre de la llave y $value el valor
                             * Se ve si el valor tiene hijos
                             */
                            if ($relationship[$i][$key] != null || $relationship[$i][$key] != '') {

                                $valueArray = explode('.', $value);

                                $count      = count($valueArray);
                                $subNivel   = '';

                                if ($count > 1) {
                                    for ($j = 0; $j < $count; $j++) {
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
                    $responseArray = $this->responseFilterMios($relationship, $formId);
                    return $responseArray;
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } catch (\Throwable $th) {
            return null;
        }
    }

    // Funcion para hacer la consulta http por curl
    function httpRequest($mode, $url, $autorization_type, $token, $other_autorization_type, $other_token, $json_send, $api_type)
    {
        $miosHelper     = new MiosHelper();
        if ($mode == 'GET') {
            $headersData = array();

            if ($autorization_type != null || $token != null) {
                $headersData['Autorization'] =  $autorization_type . ' ' . $token;
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

            if ($autorization_type != null || $token != null) {
                $auth  = isset($autorization_type) ? $autorization_type . ' ' . $token : $token;
                $autorization = "Authorization:$auth";
            }
            if ($other_autorization_type != null && $other_token != null) {
                $otherAutorization = "$other_autorization_type:$other_token";
            }
            // Se llenan los headers
            if (isset($autorization) && isset($otherAutorization)) {
                $headers = array("Content-Type:application/json", "$autorization", "$otherAutorization");
            } elseif (isset($autorization)) {
                $headers = array("Content-Type:application/json", "$autorization");
            } elseif (isset($otherAutorization)) {
                $headers = array("Content-Type:application/json", "$otherAutorization");
            } else {
                $headers = array("Content-Type:application/json");
            }


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

    // Funcion para formatear la respuesta de filtor
    function responseFilterMios($relationship, $formId)
    {

        $rrhh = [
            'first_name'    => null,
            'last_name'     => null,
            'id_number'     => null,
            'id_type'       => null, 
            'email'         => null, 
            'phone'         => null,
            'campaign_id'   => null,
        ];
        $userData = [
            'id'        => 0,
            'rrhh_id'   => 0,
            'group_id'  => null,
            'username'  => null,
            'email'     => null,
            'deleted_at'=> null,
            'rrhh'      => $rrhh,
            'roles'     => [],
        ];
        $responseArray  = [
            'id'                => 0,
            'user_id'           => 0,
            'form_id'           => $formId,
            'client_id'         => 0,
            'created_at'        => null,
            'updated_at'        => null,
            'structure_answer'  => $relationship,
            'userdata'          => $userData,
            'client'            => []
        ];

        return $responseArray;
    }

    // Funcion para logear en el api registrado
    function loginApi($dataLogin)
    {
        $miosHelper                 = new MiosHelper();
        $mode                       = $dataLogin['mode'];
        $url                        = $dataLogin['url'];
        $autorization_type          = $dataLogin['autorization_type'];
        $token                      = $dataLogin['token'];
        $other_autorization_type    = $dataLogin['other_autorization_type'];
        $other_token                = $dataLogin['other_token'];
        $json_send                  = $dataLogin['json_send'];
        $response_token             = $dataLogin['response_token'];
        $api_type                   = $dataLogin['api_type'];

        // Se hace la petición de login
        $result = $this->httpRequest($mode, $url, $autorization_type, $token, $other_autorization_type, $other_token, $json_send, $api_type);
        $apiData = $miosHelper->jsonDecodeResponse(json_encode($result));
        $positionArray = explode('.', $response_token);
        $count      = count($positionArray);
        $subNivel   = [];
        $tokenApi    = '';
        if ($count > 1) {
            for ($i = 0; $i < $count; $i++) {
                if ($i == 0) {
                    $subNivel   = $apiData[$positionArray[$i]];
                } else {
                    $subNivel   = $subNivel[$positionArray[$i]];
                }
            }
            $tokenApi = $subNivel;
        } else {
            $tokenApi = $apiData[$positionArray[0]];
        }
        $arrayApi = ['token' => $tokenApi];
        $where  = ['status' => 1, 'form_id' => $dataLogin['form_id'], 'request_type' => 2];
        ApiConnection::where($where)->update($arrayApi);
        return true;
    }
}
