<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormType;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Helpers\MiosHelper;
use App\Models\KeyValue;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FormReportExport;


class FormController extends Controller
{
    /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para consultar los formularios existentes en la BD
     */
    public function FormsList()
    {
        $forms = DB::table('forms')
            ->join('form_types', 'forms.form_type_id', '=', 'form_types.id')
            ->select('name_form', 'forms.id', 'name_type', 'state')
            ->get();
        return $forms;
    }

    /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para consultar el formulario con sus respectivas secciones
     */
    public function searchForm($id)
    {
        $formsSections = Form::where('id', $id)
            ->with('section')
            ->select('*')
            ->first();
        $formsSections->filters = json_decode($formsSections->filters);
        for ($i = 0; $i < count($formsSections->section); $i++) {
            unset($formsSections->section[$i]['created_at']);
            unset($formsSections->section[$i]['updated_at']);
            unset($formsSections->section[$i]['form_id']);
            $formsSections->section[$i]['fields'] = json_decode($formsSections->section[$i]['fields']);
        }

        return response()->json($formsSections);
    }

    /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para crear el formulario y sus secciones
     */
    public function saveForm(Request $request, MiosHelper $miosHelper)
    {
        try {
            $forms = new Form([
                'group_id' =>  $request->input('group_id'),
                'campaign_id' => 1,
                'form_type_id' => $request->input('type_form'),
                'name_form' => $request->input('name_form'),
                'filters' => json_encode($request->filters),
                'state' => $request->state
            ]);
            $forms->save();

            foreach ($request['sections'] as $section) {
                for ($i = 0; $i < count($section['fields']); $i++) {
                    if ($section['sectionName'] == 'Datos básicos del cliente') {
                        $sect = $miosHelper->validateKeyName($section['fields'][0]['label'], $section['fields'][1]['label'], $section['fields'][2]['label'], $section['fields'][3]['label'], $section['fields'][4]['label'], $section['fields'][5]['label'], $section['fields'][6]['label'], $section['fields'][7]['label'], $section);
                    } else {
                        $section['fields'][$i]['key'] = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $section['fields'][$i]['label']);
                        $section['fields'][$i]['key'] =  strtolower(str_replace(' ', '-', $section['fields'][$i]['label']));
                    }
                }
                if ($section['sectionName'] == 'Datos básicos del cliente') {
                    $firstSection = new Section([
                        'id' => $section['idsection'],
                        'form_id' => $forms->id,
                        'name_section' => $section['sectionName'],
                        'type_section' => $section['type_section'],
                        'fields' => json_encode($sect)
                    ]);
                    $firstSection->save();
                } else {
                    $fields = $section['fields'];
                    $sections = new Section([
                        'id' => $section['idsection'],
                        'form_id' => $forms->id,
                        'name_section' => $section['sectionName'],
                        'type_section' => $section['type_section'],
                        'fields' => json_encode($fields)
                    ]);
                    $sections->save();
                }
            }

            $data = ['forms' => $forms, 'firstSection' => json_decode($firstSection->fields), 'sections' => json_decode($sections->fields), 'code' => 200, 'message' => 'Formulario Guardado Correctamente'];

            return response()->json($data, $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al guardar el formulario', 500);
        }
    }


    /**
     * Nicoll Ramirez
     * 04-02-2021
     * Método para consultar el tipo de formulario en el select de creación de formulario
     */

    public function searchFormType()
    {
        $formtype = FormType::select('id', 'name_type')->get();
        return $formtype;
    }
    /**
     *Nicoll Ramirez
     *23-02-2021
     *Método para editar el formulario
     */


    public function editForm(Request $request, $id, MiosHelper $miosHelper)
    {
        try {
            $form = Form::find($id);
            $form->group_id = $request->group_id;
            $form->form_type_id = $request->type_form;
            $form->name_form = $request->name_form;
            $form->filters = json_encode($request->filters);
            $form->save();

            foreach ($request->sections as $section) {
                for ($i = 0; $i < count($section['fields']); $i++) {
                    if ($section['sectionName'] == 'Datos básicos del cliente') {
                        $firstName = isset($section['fields'][0]['label']) ? $section['fields'][0]['label'] : null;
                        $middleName = isset($section['fields'][1]['label']) ? $section['fields'][1]['label'] : null;
                        $lastName = isset($section['fields'][2]['label']) ? $section['fields'][2]['label'] : null;
                        $secondLastName = isset($section['fields'][3]['label']) ? $section['fields'][3]['label'] : null;
                        $document = isset($section['fields'][4]['label']) ? $section['fields'][4]['label'] : null;
                        $phone = isset($section['fields'][5]['label']) ? $section['fields'][5]['label'] : null;
                        $email = isset($section['fields'][6]['label']) ? $section['fields'][6]['label'] : null;
                        $documentTypeId = isset($section['fields'][7]['label']) ? $section['fields'][7]['label'] : null;

                        $sect = $miosHelper->validateKeyName($firstName, $middleName, $lastName, $secondLastName, $document, $phone, $email, $documentTypeId, $section);
                    } else {
                        $section['fields'][$i]['key'] = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $section['fields'][$i]['label']);
                        $section['fields'][$i]['key'] =  strtolower(str_replace(' ', '-', $section['fields'][$i]['label']));
                    }
                }
                if ($section['sectionName'] == 'Datos básicos de cliente') {
                    $sections = Section::find($section['idsection']);
                    $sections->name_section = $section['sectionName'];
                    $sections->type_section = $section['type_section'];
                    $sections->fields = json_encode($sect);
                    $sections->save();
                } else {
                    $fields = $section['fields'];
                    $sections = Section::find($section['idsection']);
                    $sections->name_section = $section['sectionName'];
                    $sections->type_section = $section['type_section'];
                    $sections->fields = json_encode($fields);
                    $sections->save();
                }
            }
            $data = ['forms' => $form, 'sections' => json_decode($sections->fields), 'code' => 200, 'message' => 'Formulario editado Correctamente'];

            return response()->json($data, $data['code']);
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al editar el formulario', 500);
        }
    }

    /**
     * Nicoll Ramirez
     * 23-02-2021
     * Método para desactivar el estado del formulario
     */
    public function deleteForm(Request $request, $id)
    {
        try {
            $form = Form::find($id);
            $form->state = $request->state;
            $form->save();

            return $this->successResponse('Formulario desactivado correctamente');
        } catch (\Throwable $e) {
            return $this->errorResponse('Error al desactivar el formulario', 500);
        }
    }

    public function report($form_id, $fecha_desde, $fecha_hasta, $parameters)
    {
        $headers    = utf8_encode(base64_decode($parameters));
        // $formReport->headersExcel(explode(",", $headers));
        //return Excel::download($formReport, 'reporte_formulario.xlsx');

        return Excel::download(new FormReportExport($form_id, $fecha_desde, $fecha_hasta, $headers), 'reporte_formulario.xlsx');
    }

    /**
     * Olme Marin
     * 25-03-2021
     * Método para consultar el listado de los formularios asignados a un usuario por grupo
     */
    public function formsByUser(MiosHelper $miosHelper, $idUser)
    {

        try {
            // Se obtienes los grupor por usuarios
            $groupsIds  = $miosHelper->groupsByUserId($idUser);
            $where      = ['state' => 1, 'group_id' => $groupsIds];
            $forms      = Form::where($where)->get();
            foreach ($forms as $form) {
                $form->filters = $miosHelper->jsonDecodeResponse($form->filters);
            }
            $data       = $miosHelper->jsonResponse(true, 200, 'forms', $forms);
        } catch (\Throwable $th) {
            $data       = $miosHelper->jsonResponse(true, 500, 'message', 'Ha ocurrido un error: ' . $th);
        }


        return response()->json($data, $data['code']);
    }
}
