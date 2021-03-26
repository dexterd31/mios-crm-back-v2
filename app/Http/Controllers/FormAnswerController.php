<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
use App\Models\Client;
use App\Models\KeyValue;
use App\Models\Directory;
use App\Models\ApiConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\CiuService;
use App\Services\NominaService;
use Helpers\MiosHelper;
use Helpers\FormAnswerHelper;

class FormAnswerController extends Controller
{
    private $ciuService;
    private $nominaService;

    public function __construct(CiuService $ciuService, NominaService $nominaService)
    {
        $this->middleware('auth');
        $this->ciuService = $ciuService;
        $this->nominaService = $nominaService;
    }
    /**
     * Nicol Ramirez
     * 11-02-2020
     * Método para guardar la información del formulario
     */
    public function saveinfo(Request $request, MiosHelper $miosHelper, FormAnswerHelper $formAnswerHelper)
    {
        // try {
            // Se valida si tiene permiso para hacer acciones en formAnswer
            if (Gate::allows('form_answer')) {
                $json_body = $miosHelper->jsonDecodeResponse($request->getContent());
                $client = null;

                $structure_answer = $formAnswerHelper->structureAnswer($json_body['form_id'], $json_body['sections']);

                if ($json_body['client_id'] == null || $json_body['client_id'] == "") {
                    $contador = 0;
                    foreach ($json_body['sections'] as $section) {
                        if ($contador == 0) {
                            $client = new Client([
                                'document_type_id' => !empty($section['document_type_id']) ? $section['document_type_id'] : 1,
                                'first_name' => rtrim($section['firstName']),
                                'middle_name' => rtrim($section['middleName']),
                                'first_lastname' => rtrim($section['lastName']),
                                'second_lastname' => rtrim($section['secondLastName']),
                                'document' => rtrim($section['document']),
                                'phone' => rtrim($section['phone']),
                                'email' => rtrim($section['email'])
                            ]);
                            $client->save();
                        }

                        foreach ($section as $key => $value) {
                            $sect = new KeyValue([
                                'form_id' => $json_body['form_id'],
                                'client_id' => $client->id,
                                'key' => $key,
                                'value' => $value,
                                'description' => null
                            ]);

                            $sect->save();
                        }
                        $contador++;
                    }

                    $form_answer = new FormAnswer([
                        'user_id' => 1,
                        'channel_id' => 1,
                        'client_id' => $client->id,
                        'form_id' => $json_body['form_id'],
                        'structure_answer' => json_encode($structure_answer)
                    ]);

                    $form_answer->save();
                    $message = 'Informacion guardada correctamente';
                } else {
                    foreach ($json_body['sections'] as $section) {
                        foreach ($section as $key => $value) {
                            $sec = new KeyValue([
                                'form_id' => $json_body['form_id'],
                                'client_id' => $json_body['client_id'],
                                'key' => $key,
                                'value' => $value,
                                'description' => null
                            ]);

                            $sec->save();
                        }
                    }
                    $formanswer = new FormAnswer([
                        'user_id' => 1,
                        'channel_id' => 1,
                        'client_id' => $json_body['client_id'],
                        'form_id' => $json_body['form_id'],
                        'structure_answer' => json_encode($structure_answer)
                    ]);

                    $formanswer->save();
                    $message = 'Informacion guardada correctamente';
                }
            } else {
                $message = 'Tú rol no tiene permisos para ejecutar esta acción';
            }
            return $this->successResponse($message);
        // } catch (\Throwable $e) {
        //     return $this->errorResponse('Error al guardar la gestion', 500);
        // }
    }

    /**
     * Olme Marin
     * 26-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request, MiosHelper $miosHelper, FormAnswerHelper $formAnswerHelper)
    {
        try {
            if (Gate::allows('form_answer')) {

                $json_body      = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $request->getContent()), true);
                $formId         = $json_body['form_id'];
                $form_answers   = null;

                if (isset($json_body['item1_key']) && isset($json_body['item1_value']) && isset($json_body['item2_key']) && isset($json_body['item2_value']) && isset($json_body['item3_key']) && isset($json_body['item3_value'])) {
                    $item1key   = !empty($json_body['item1_key']) ? $json_body['item1_key'] : 'vacio';
                    $item1value = !empty($json_body['item1_value']) ? $json_body['item1_value'] : 'vacio';
                    $item2key   = !empty($json_body['item2_key']) ? $json_body['item2_key'] : 'vacio';
                    $item2value = !empty($json_body['item2_value']) ? $json_body['item2_value'] : 'vacio';
                    $item3key   = !empty($json_body['item3_key']) ? $json_body['item3_key'] : 'vacio';
                    $item3value = !empty($json_body['item3_value']) ? $json_body['item3_value'] : 'vacio';

                    // Se busca si la solicitud tiene cargue por api
                    $where = ['form_id' => $formId, 'request_type' => 2, 'status' => 1];
                    $apiFind = ApiConnection::where($where)->first();
                    $parameter = null;
                    if ($apiFind) {
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
                        $infoApi = $formAnswerHelper->getInfoByApi($apiFind, $parameter, $formId);

                        $form_answers = $infoApi;
                        $ff = [];
                        array_push($ff, $form_answers);

                        $form_answers = $miosHelper->paginate($ff, $perPage = 15, $page = null);
                    }

                    if ($form_answers == null) {

                        // Se continua la busqueda por gestio o base de datos
                        $form_answers = FormAnswer::where('form_id', $formId)
                            ->where('structure_answer', 'like', '%' . $item1value . '%')
                            ->orWhere('structure_answer', 'like', '%' . $item2value . '%')
                            ->orWhere('structure_answer', 'like', '%' . $item3value . '%')
                            ->with('client')->paginate(10);

                        // Si no se encuatra registros se busca por cliente
                        if (count($form_answers) < 1) {
                            $clientInfo = Client::Where('document', 'like', '%' . $item1value . '%')
                                ->orWhere('document', 'like', '%' . $item2value . '%')
                                ->orWhere('document', 'like', '%' . $item3value . '%')->select('id')->first();
                            $clientNum = $clientInfo != null ? json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $clientInfo->id)) : null;
                            if ($clientNum) {
                                $form_answers = FormAnswer::where('form_id', $formId)
                                    ->where('client_id', $clientNum)
                                    ->with('client')->paginate(10);
                            }
                            // Se busca en directory
                            if (count($form_answers) < 1) {
                                $form_answers = Directory::where('form_id', $formId)
                                    ->where('client_id', $clientNum)
                                    ->with('client')->paginate(10);
                            }
                            if (count($form_answers) < 1) {
                                $form_answers = Directory::where('form_id', $formId)
                                    ->where('data', 'like', '%' . $item1value . '%')
                                    ->orWhere('data', 'like', '%' . $item2value . '%')
                                    ->orWhere('data', 'like', '%' . $item3value . '%')
                                    ->with('client')->paginate(10);
                            }
                        }
                        foreach ($form_answers as $form) {
                            $userData       = $this->ciuService->fetchUser($form->user_id)->data;
                            $form->structure_answer = $form->data != null ? json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $form->data), true) : json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $form->structure_answer), true);
                            $form->userdata = $userData;
                            unset($form->data);
                        }
                    }
                    $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
                } else {
                    $data = $miosHelper->jsonResponse(false, 404, 'message', 'No ha enviado todas las llaves');
                }
            } else {
                $data = $miosHelper->jsonResponse(false, 403, 'message', 'Tú rol no tiene permisos para ejecutar esta acción');
            }
            return response()->json($data, $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al buscar la gestion', 500);
        }
    }

    /**
     * Nicoll Ramirez
     * 22-02-2021
     * Método para consultar el tipo de documento en las respuestas del formulario
     */
    public function searchDocumentType()
    {
        $documentType = DB::table('document_types')
            ->select('id', 'name_type_document')->get();

        return $documentType;
    }

    /**
     * Olme Marin
     * 02-03-2021
     * Método para consultar los registro de un cliente en from answer
     */
    public function formAnswerHistoric($form_id, $client_id, MiosHelper $miosHelper)
    {
        try {

            $where = ['form_id' => $form_id, 'client_id' => $client_id];
            $form_answers = FormAnswer::where($where)->with('channel')->paginate(10);
            foreach ($form_answers  as $form) {
                $userData     = $this->ciuService->fetchUser($form->user_id)->data;
                $form->user = $userData;
            }

            $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
            return response()->json($data, $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al buscar la gestion', 500);
        }
    }
}
