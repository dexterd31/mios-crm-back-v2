<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiConnection;
use Helpers\MiosHelper;

class ApiConnectionController extends Controller
{
    /**
     * Olme Marin
     * 20-03-2020
     * En la tabla de api_connections se debe tener encuenta
     * request_type = 1.Es para el tipo de requrimiento es login 2.Para solicitude de información 
     * mode = Es para identificar el modo de hacer la peticion POST - GET - PUT - DELETE
     * api_type = Para saber que tipo de api es la solicitud 1.Api rest 2.Graphql 
     * autorization_type = saber que tipo de autenticación maneja la api Bearer,OAuth,Hawk etc. 
     * parameter = Comodin para saber si la url recibe un parametro es debe llegar sin espacios 
     */

    /**
     * Olme Marin
     * 20-03-2020
     * Método para guardar la conexión api de un formulario
     */
    public function save(Request $request, MiosHelper $miosHelper)
    {
        // Recoger los datos por post
        $json_body = $miosHelper->jsonDecodeResponse($request->getContent());
        if (!empty($json_body)) {
            if ($json_body['json_response'] == NULL) {
                $data   = $miosHelper->jsonResponse(false, 404, 'message', 'Se debe enviar un ejemplo de la respuesta de la solicitud del api.');
            } else {
                // Validar si el registro a guardar es de tipo login 
                if ($json_body['request_type'] == 1) {
                    // Se valida si el formulario ya tiene un registro de login
                    $where = ['form_id' => $json_body['form_id'], 'request_type' => 1, 'status' => 1];
                    $login = ApiConnection::where($where)->first();
                    if ($login) {
                        $data = $miosHelper->jsonResponse(false, 400, 'message', 'Ya existe un registro login en el sistema para esta api');
                    } else {
                        // Se guarda el registro login 
                        $api                            = new ApiConnection();
                        $api->name                      = $json_body['name'];
                        $api->url                       = $json_body['url'];
                        $api->autorization_type         = $json_body['autorization_type'];
                        $api->token                     = $json_body['token'];
                        $api->other_autorization_type   = $json_body['other_autorization_type'];
                        $api->other_token               = $json_body['other_token'];
                        $api->mode                      = $json_body['mode'];
                        $api->parameter                 = trim($json_body['parameter']);
                        $api->json_send                 = json_encode($json_body['json_send']);
                        $api->json_response             = json_encode($json_body['json_response']);
                        $api->request_type              = $json_body['request_type'];
                        $api->api_type                  = $json_body['api_type'];
                        $api->status                    = true;
                        $api->form_id                   = $json_body['form_id'];
                        $api->save();
                        $data = $miosHelper->jsonResponse(true, 200, 'api_conection', $api);
                    }
                } else {
                    $api                            = new ApiConnection();
                    $api->name                      = $json_body['name'];
                    $api->url                       = $json_body['url'];
                    $api->autorization_type         = $json_body['autorization_type'];
                    $api->token                     = $json_body['token'];
                    $api->other_autorization_type   = $json_body['other_autorization_type'];
                    $api->other_token               = $json_body['other_token'];
                    $api->mode                      = $json_body['mode'];
                    $api->parameter                 = trim($json_body['parameter']);
                    $api->json_send                 = json_encode($json_body['json_send']);
                    $api->json_response             = json_encode($json_body['json_response']);
                    $api->request_type              = $json_body['request_type'];
                    $api->api_type                  = $json_body['api_type'];
                    $api->status                    = true;
                    $api->form_id                   = $json_body['form_id'];
                    $api->save();
                    $data = $miosHelper->jsonResponse(true, 200, 'api_conection', $api);
                }
            }
        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message', 'Faltan campos por diligenciarse');
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para en listar la conexión api activas de un formulario
     */
    public function list(MiosHelper $miosHelper, $form_id)
    {
        $where  = ['form_id' => $form_id, 'status' => 1];
        $api    = ApiConnection::where($where)->paginate(10);
        if (empty($api)) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'No se han encontrado registros');
        } else {
            $data = $miosHelper->jsonResponse(true, 200, 'apis', $api);
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para obtener la conexión api activa de un formulario por id
     */
    public function get(MiosHelper $miosHelper, $id)
    {
        try {
            $api = ApiConnection::where('id', $id)->first()->load('form');
            $data = $miosHelper->jsonResponse(true, 200, 'api', $api);
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'No se encontro registro con ese id');
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para en actualizar la conexión api activa de un formulario
     */
    public function update(Request $request, MiosHelper $miosHelper, $id)
    {
        // Recoger los datos por post
        $json_body = $miosHelper->jsonDecodeResponse($request->getContent());
        if (!empty($json_body)) {
            //Eliminar lo que no queremos actualizar 
            unset($json_body['id']);
            unset($json_body['created_at']);
            if ($json_body['json_response'] == NULL) {
                $data   = $miosHelper->jsonResponse(false, 404, 'message', 'Se debe enviar un ejemplo de la respuesta de la solicitud del api.');
            } else {
                //Obtener el registro a actualizar 
                $api = ApiConnection::where('id', $id)->first();
                if (!empty($api) && is_object($api)) {
                    $json_body['json_response'] = json_encode($json_body['json_response']);
                    $json_body['status']        = 1;
                    $api    = ApiConnection::where('id', $id)->update($json_body);
                    $data   = $miosHelper->jsonResponse(true, 200, 'api', $json_body);
                } else {
                    $data   = $miosHelper->jsonResponse(false, 404, 'message', 'No se encontro un registro con ese id');
                }
            }
        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message', 'Faltan campos por diligenciarse');
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para en cambiar el estado de una conexión api activas de un formulario
     */
    public function delete(MiosHelper $miosHelper, $id)
    {
        try {
            $api = ApiConnection::where('id', $id)->first();
            if (!empty($api) && is_object($api)) {
                $api = $miosHelper->jsonDecodeResponse($api);
                unset($api['id']);
                unset($api['created_at']);
                unset($api['updated_at']);
                $api['status'] = 0;
                $api = ApiConnection::where('id', $id)->update($api);
                $data = $miosHelper->jsonResponse(true, 200, 'api', 'Se elimino la api');
            } else {
                $data = $miosHelper->jsonResponse(false, 404, 'message','No se encontro la api');
            }
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'Ha ocurrido un error');
        }
        return response()->json($data, $data['code']);
    }
}
