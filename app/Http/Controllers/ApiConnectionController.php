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
    */
    
    /**
     * Olme Marin
     * 20-03-2020
     * Método para guardar la conexión api de un formulario
    */
    public function save(Request $request, MiosHelper $miosHelper) {
        // Recoger los datos por post
        $json_body = $miosHelper->jsonDecodeResponse($request->getContent());
        if (!empty($json_body)) {
            // Validar si el registro a guardar es de tipo login 
            
            
        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message','Faltan campos por diligenciarse');
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Olme Marin
     * 20-03-2020
     * Método para en listar la conexión api activas de un formulario
    */
    public function list(Request $request, MiosHelper $miosHelper) {}

    /**
     * Olme Marin
     * 20-03-2020
     * Método para obtener la conexión api activa de un formulario por id
    */
    public function get(Request $request, MiosHelper $miosHelper) {}

    /**
     * Olme Marin
     * 20-03-2020
     * Método para en actualizar la conexión api activa de un formulario
    */
    public function update(Request $request, MiosHelper $miosHelper) {}

    /**
     * Olme Marin
     * 20-03-2020
     * Método para en cambiar el estado de una conexión api activas de un formulario
    */
    public function delete(Request $request, MiosHelper $miosHelper) {}


}
