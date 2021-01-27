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

        $forms = DB::table('forms')->select('key','name','description')->get();

        return (compact('forms'));
    }
     /**
     * Nicol Ramirez
     * 27-01-2020
     * Método para consultar el formulario con sus respectivas secciones
     */
    public function searchForm()
    {
       $formSections = DB::table('forms')
       ->join('formtypes', 'forms.form_type_id', '=', 'formtypes.id')
       ->join('sections', 'sections.form_id', '=', 'forms.id')
       ->select('formtypes.name', 'forms.name', 'forms.description', 'sections.name', 'sections.fields')
       ->get();

       return (compact('formSections'));
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
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'key' => $request->input('key')
        ]);
       $forms->save();
            

       foreach($request->input('sections') as $section){
           $sections = new Section([
               'form_id' => $forms->id,
               'name' => $section['sectionName'],
               'fields' => json_encode($section['fields'])
           ]);
           $sections->save();           
        }

        return ('guardado');
       
       
    }

    

    
}
