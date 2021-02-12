<?php

namespace App\Http\Controllers;
use App\Models\Form;
use App\Models\FormType;
use App\Models\Section;
use App\Models\Group;
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
       /*  $forms = DB::table('forms')
                    ->join('formtypes','forms.form_type_id', '=', 'formtypes.id')
                    ->select('forms.id','form_type_id','campaign_id','group_id','forms.key','name_form','formtypes.name_type')
                    ->where('campaign_id','1')
                    ->where('group_id','1')->get(); */

        $forms= Form::where('campaign_id',1)
                    ->where('group_id',1)
                    ->with('formtype')
                    ->get();
                    // dd($forms[0]);
        for($i=0; $i<count($forms); $i++)
        {    
            unset($forms[$i]->formtype['id']);
            unset($forms[$i]->formtype['description']);
            unset($forms[$i]->formtype['key']);
            unset($forms[$i]->formtype['updated_at']);
            unset($forms[$i]->formtype['created_at']);
        } 
        // dd($forms);
           
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
                               ->select('id','name_form')
                               ->first();

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

        try{
          /*   $groups = new Group([
                'user_id' => $request->input('user'),
                'name_group' => $request->input('name_group')
            ]);
            $groups->save(); */

            $forms = new Form([
                'group_id' => '1',
                'campaign_id' => $request->input('campaign'),
                'form_type_id' => $request->input('type_form'),
                'name_form' => $request->input('name_form'),
                'key' => $request->input('key')
                ]);
                $forms->save();

           foreach($request->input('sections') as $section)
           {
              $section['fields'][0]['key']=str_replace(' ', '',$section['fields'][0]['label']);
              $var=$section['fields'];
               $sections = new Section([
                   'form_id' => $forms->id,
                   'name_section' => $section['sectionName'],
                   'fields' => json_encode($var),
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
    
    
}
