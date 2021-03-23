<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiQuestion;
use Helpers\MiosHelper;

class ApiQuestionController extends Controller
{
    /**
     * Olme Marin
     * 20-03-2020
     * Método para guardar la relación pregunta - servicio api
    */
    public function save(Request $request, MiosHelper $miosHelper)
    {
        try {
            // Recoger los datos por post
            $json_body = $miosHelper->jsonDecodeResponse($request->getContent());
            $where = ['form_id' => $json_body['form_id'], 'api_id' => $json_body['api_id']];
            $relationshipFind = ApiQuestion::where($where)->first();
            if ($relationshipFind) {
                $data = $miosHelper->jsonResponse(false, 400, 'message', 'Este formulario ya tiene una relación activa');
            } else {
                $relationship = new ApiQuestion();
                $relationship->relationship = json_encode($json_body['relationship']);
                $relationship->form_id      = $json_body['form_id'];
                $relationship->api_id       = $json_body['api_id'];
                $relationship->save();
                $data = $miosHelper->jsonResponse(true, 200, 'relationship', $relationship);
            }
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(false, 400, 'message', 'Ha ocurrido un error');
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para listar las relaciones pregunta - servicio api
     */
    public function list(MiosHelper $miosHelper, $form_id)
    {
        $where  = ['form_id' => $form_id, 'status' => 1];
        $relationship = ApiQuestion::where($where)->paginate(10);
        if (empty($relationship)) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'No se han encontrado registros');
        } else {
            $data = $miosHelper->jsonResponse(true, 200, 'relationship', $relationship);
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para obtener la relación pregunta - servicio api
     */
    public function get(MiosHelper $miosHelper, $id)
    {
        try {
            $relationship = ApiQuestion::where('id', $id)->first()->load('form');
            $data = $miosHelper->jsonResponse(true, 200, 'relationship', $relationship);
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'No se encontro registro con ese id');
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para actualizar la relación pregunta - servicio api
     */
    public function update(Request $request, MiosHelper $miosHelper, $id)
    {
        try {
            // Recoger los datos por post
            $json_body = $miosHelper->jsonDecodeResponse($request->getContent());
            if (!empty($json_body)) {
                //Eliminar lo que no queremos actualizar 
                unset($json_body['id']);
                unset($json_body['created_at']);
                //Obtener el registro a actualizar 
                $relationship = ApiQuestion::where('id', $id)->first();
                if (!empty($relationship) && is_object($relationship)) {
                    $json_body['relationship'] = json_encode($json_body['relationship']);
                    $relationship    = ApiQuestion::where('id', $id)->update($json_body);
                    $data   = $miosHelper->jsonResponse(true, 200, 'relationship', $json_body);
                } else {
                    $data   = $miosHelper->jsonResponse(false, 404, 'message', 'No se encontro un registro con ese id');
                }
            } else {
                $data = $miosHelper->jsonResponse(false, 400, 'message', 'Faltan campos por diligenciarse');
            }
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(false, 400, 'message', 'Ha ocurrido un error: ' . $th);
        }

        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para cambiar estado de la relación pregunta - servicio api
     */
    public function delete(MiosHelper $miosHelper, $id)
    {
        try {
            $relationship = ApiQuestion::where('id', $id)->first();
            if (!empty($relationship) && is_object($relationship)) {
                $relationship = $miosHelper->jsonDecodeResponse($relationship);
                unset($relationship['id']);
                unset($relationship['created_at']);
                unset($relationship['updated_at']);
                $relationship['status'] = 0;
                $relationship = ApiQuestion::where('id', $id)->update($relationship);
                $data = $miosHelper->jsonResponse(true, 200, 'api', 'Se elimino la relación');
            } else {
                $data = $miosHelper->jsonResponse(false, 404, 'message', 'No se encontro la relación');
            }
        } catch (\Throwable $th) {
            $data = $miosHelper->jsonResponse(false, 404, 'message', 'Ha ocurrido un error: ' . $th);
        }
        return response()->json($data, $data['code']);
    }
}


