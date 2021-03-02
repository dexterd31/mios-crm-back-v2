<?php

namespace App\Http\Controllers;
use App\Models\Form;
use App\Models\FormType;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


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
        ->join('form_types','forms.form_type_id','=','form_types.id')   
        ->select('name_form','forms.id','name_type','state')
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
        $formsSections = Form::where('id',$id)
                               ->with('section')
                               ->select('*')
                               ->first();
        $formsSections->filters = json_decode($formsSections->filters);
        for($i=0; $i<count($formsSections->section); $i++)
        {    
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
    public function saveForm(Request $request)
    {
        try
        {
            $forms = new Form([
               'group_id' =>  $request->input('group_id'),
                'campaign_id' => 1,
                'form_type_id' => $request->input('type_form'),
                'name_form' => $request->input('name_form'),
                'filters' => json_encode($request->filters),
                'state' => $request->state
                ]);
                $forms->save();

           foreach($request->input('sections') as $section)
           {
              $section['fields'][0]['key'] = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'],$section['fields'][0]['label']);
              $section['fields'][0]['key'] =  strtolower( str_replace(' ','-',$section['fields'][0]['label']) );
              $sect = $section['fields'];
               $sections = new Section([
                   'form_id' => $forms->id,
                   'name_section' => $section['sectionName'],
                   'type_section' => $section['type_section'],
                   'fields' => json_encode($sect),
               ]);
               $sections->save();           
            }

            return $this->successResponse('Guardado Correctamente');
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al guardar el formulario',500);
        }
    }
    
    /**
     * Nicoll Ramirez
     * 04-02-2021
     * Método para consultar el tipo de formulario en el select de creación de formulario
     */
    public function searchFormType(){
        $formtype = FormType::select('id','name_type')->get();
        return $formtype;
    }
    /**
     *Nicoll Ramirez 
     *23-02-2021
     *Método para editar el formulario
     */
    
    public function editForm(Request $request, $id)
    {
        try
        {
            $form = Form::find($id);
            $form->group_id = $request->group_id;
            $form->form_type_id = $request->type_form;
            $form->name_form = $request->name_form;
            $form->filters = json_encode($request->filters);
            $form->save();

            foreach($request->sections as $section)
            {
                $section['fields'][0]['key'] = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'],$section['fields'][0]['label']);
                $section['fields'][0]['key'] =  strtolower( str_replace(' ','-',$section['fields'][0]['label']) );
                $var = $section['fields'];

                $result = Section::find($section['id']);
                $result->name_section = $section['sectionName'];
                $result->type_section = $section['type_section'];
                $result->fields = json_encode($var);
                $result->save();           
            } 

        return $this->successResponse('Formulario editado Correctamente');
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al editar el formulario',500);
        }
    }

        /**
         * Nicoll Ramirez
         * 23-02-2021
         * Método para desactivar el estado del formulario
         */
    public function deleteForm(Request $request, $id)
    {
        try
        {
            $form = Form::find($id);
            $form->state = $request->state;
            $form->save();

            return $this->successResponse('Formulario desactivado correctamente');
    
        }catch(\Throwable $e){
            return $this->errorResponse('Error al desactivar el formulario',500);
        }
       
    }
}
