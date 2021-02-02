<?php

namespace App\Http\Controllers;
use App\Models\Form;
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
        $forms = Form::select('key','name_form','description')->get();
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
                               ->select('id','name_form','description')
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
        $forms = new Form([
            'form_type_id' => $request->input('type'),
            'name_form' => $request->input('name'),
            'description' => $request->input('description'),
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

        return ('guardado');
    }


    
}
