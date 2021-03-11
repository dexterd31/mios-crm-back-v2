<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswer;
use App\Models\Client;
use App\Models\KeyValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\CiuService;
use App\Services\NominaService;
use Helpers\MiosHelper;

class FormAnswerController extends Controller
{
    private $ciuService;
    private $nominaService;

     public function __construct(CiuService $ciuService, NominaService $nominaService)
    {
       // $this->middleware('auth');
        $this->ciuService = $ciuService;
        $this->nominaService = $nominaService;
    }  
    /**
     * Nicol Ramirez
     * 11-02-2020
     * Método para guardar la información del formulario
     */
    public function saveinfo(Request $request)
    {
        try {
            // Se valida si tiene permiso para hacer acciones en formAnswer
           // if (Gate::allows('form_answer')) {
                $json_body = json_decode($request->getContent());
                $client = null;
                if ($json_body->client_id == null) {
                    $contador = 0;
                    foreach ($json_body->sections as $section) {

                        if ($contador == 0) {
                            $client = new Client([
                                'document_type_id' => !empty($section->document_type_id) ? $section->document_type_id : 1,
                                'first_name' => $section->firstName,
                                'middle_name' => $section->middleName,
                                'first_lastname' => $section->lastName,
                                'second_lastname' => $section->secondLastName,
                                'document' => $section->document,
                                'phone' => $section->phone,
                                'email' => $section->email
                            ]);
                            $client->save();
                        }

                        foreach ($section as $key => $value) {
                            $sect = new KeyValue([
                                'form_id' => $json_body->form_id,
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
                        'form_id' => $json_body->form_id,
                        'structure_answer' => json_encode($json_body->sections)
                    ]);

                    $form_answer->save();
                    $message = 'Informacion guardada correctamente';
                } 
             /* } else {
                $message = 'Tú rol no tiene permisos para ejecutar esta acción';
            }  */
            return $this->successResponse($message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al guardar el formulario', 500);
        }
    }

    /**
     * Olme Marin
     * 26-02-2020
     * Método para filtrar las varias opciones en el formulario
     */
    public function filterForm(Request $request, MiosHelper $miosHelper)
    {
        try {
         //   if (Gate::allows('form_answer')) {
                $json_body = json_decode($request->getContent());

                $formId     = $json_body->form_id;

                $item1value = $json_body->item1_value;
                $item2value = $json_body->item2_value;
                $item3value = $json_body->item3_value;

                $infoForm  = [];
                $form_answers = FormAnswer::where('form_id', $formId)->get();

                foreach ($form_answers as $form) {
                    //Variable para obtener los datos del usuario que realizo la gestion
                    $form_answer_id     = $form->id;
                    $userData           = $this->ciuService->fetchUser($form->user_id)->data;
                    $channelId          = $form->channel_id;
                    $clientiId          = $form->client_id;
                    $created_at         = $form->created_at;
                    $updated_at         = $form->updated_at;
                    $array              =  json_decode(json_encode($form->structure_answer, true));
                    $structure_answer   = json_decode($array, TRUE);

                    foreach ($structure_answer as $answer) {
                        $find = false;
                        $find2 = false;
                        $find3 = false;
                        if (isset($item1value) && strlen($item1value) > 0) {
                            $find = array_search($item1value, $answer);
                        }
                        if (isset($item2value) && strlen($item2value) > 0) {
                            $find2 = array_search($item2value, $answer);
                        }
                        if (isset($item3value) && strlen($item3value) > 0) {
                            $find3 = array_search($item3value, $answer);
                        }

                        if ($find || $find2 || $find3) {
                            $info = [
                                'form_answer_id' => $form_answer_id,
                                'user' => $userData,
                                'channel_id' => $channelId,
                                'client_id' => $clientiId,
                                'created_at' => $created_at,
                                'updated_at' => $updated_at,
                                'register' => $structure_answer
                            ];
                            array_push($infoForm, $info);
                        }
                    }
                }
                $pagination = $miosHelper->paginate($infoForm, $perPage = 15, $page = null);
                $data = $miosHelper->jsonResponse(true, 200, 'result', $pagination);
          /*   } else {
                $data = $miosHelper->jsonResponse(false, 403, 'message','Tú rol no tiene permisos para ejecutar esta acción');
            } */
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
            
            $where = [ 'form_id' => $form_id, 'client_id' => $client_id ];
            $form_answers = FormAnswer::where($where)->with('channel')->paginate(10);
            foreach($form_answers  as $form) {
                $userData     = $this->ciuService->fetchUser($form->user_id)->data;
                $form->user = $userData ;
            }     
                  
            $data = $miosHelper->jsonResponse(true, 200, 'result', $form_answers);
            return response()->json($data, $data['code']);

        } catch (\Throwable $e) {
            return $this->errorResponse('Error al buscar la gestion', 500);
        }
    }

}
