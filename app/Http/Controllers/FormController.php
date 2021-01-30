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
                               ->with('Section')
                               ->select('id','name_form','description')
                               ->first();

        for($i=0; $i<count($formsSections->Section); $i++)
        {    
            unset($formsSections->section[$i]['created_at']);
            unset($formsSections->section[$i]['updated_at']);
            $formsSections->section[$i]['fields'] = json_decode($formsSections->Section[$i]['fields']);
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

    /**
     * Nicoll Ramirez 
     * 28-01-2021
     * Método para tomar el label del control creado y tomarlo como el name
     */
    public function keyControl()
    {
        $fields = DB::table('sections')->select('fields')->get();

       /*  foreach($fields as $field){
            $fields[] = [
                
            ]; */
        //}
        return $fields;
    }
}
