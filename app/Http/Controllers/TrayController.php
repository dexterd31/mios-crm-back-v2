<?php

namespace App\Http\Controllers;

use App\Models\FormAnswer;
use App\Models\Tray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $trays = Tray::all();

        if(!$trays) {
            return $this->successResponse([]);
        }

        return $this->successResponse($trays);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request['entries'];

        $tray = new Tray;
        $tray->name = $data['name'];
        $tray->form_id = $data['form_id'];
        $tray->fields = json_encode($data['fields']);
        $tray->fields_exit = json_encode($data['field_exit']);
        $tray->fields_table = json_encode($data['field_table']);
        $tray->rols = json_encode($data['rols']);
        $tray->state = 1;
        $tray->save();

        $this->matchTrayFields($tray, FormAnswer::all());

        return $this->successResponse('Bandeja creada con exito');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $trays = Tray::where('form_id', $id)->leftJoin('form_answers_trays', 'trays.id', '=', 'form_answers_trays.tray_id');

        if($request->query('showall', 0) == 0)
        {
            $trays = $trays->where('state', 1)->having(DB::raw('count(tray_id)'), '>', 0);
        }

        $trays = $trays->selectRaw('trays.*, count(tray_id) as count')
            ->groupBy('trays.id')->get();

        if(count($trays)==0) {
            return $this->successResponse([]);
        }

        // validar si el usuario actual puede visualizar trays dependiendo de su rol.
        $trays = $trays->filter(function($x){
            return count(array_intersect(auth()->user()->roles, json_decode($x->rols)));
        });

        return $this->successResponse($trays);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request['entries'];
        $tray = Tray::whereId($id)->first();
        if(!$tray) return $this->errorResponse('Bandeja no encontrada', 404);

        $tray->name = $data['name'];
        $tray->rols = $data['rols'];
        $tray->update();

        return $this->successResponse('Bandeja actualizada con exito');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tray  $tray
     * @return \Illuminate\Http\Response
     */
    public function getTray(Request $request, $id)
    {
        $tray = Tray::where('id',$id)->with('form')->first();
        // dd($trays);

        if($tray==null) {
            return $this->errorResponse('No se encontro la bandeja',404);
        }

        return $this->successResponse($tray);
    }

    public function formAnswersByTray(Request $request, $id) {

        $tray = Tray::where('id',$id)
            ->firstOrFail();

        $fieldsTable = json_decode($tray->fields_table);

        $formsAnswers = $tray->formAnswers()->get();

        // $formsAnswers = $tray->formAnswers()->paginate($request->query('n', 5))->withQueryString();

        foreach($formsAnswers as $form)
        {
            $tableValues = [];
            foreach($fieldsTable as $field)
            {
                $structureAnswer = collect(json_decode($form->structure_answer));

                $foundStructure = $structureAnswer->filter(function ($item, $key) use ($field) {
                    return $item->id == $field->id;
                })->values();
                
                if(!empty($foundStructure))
                {
                    $tableValues[] = $foundStructure[0];
                }
            }
            $form->table_values = $tableValues;
        }
        return $formsAnswers;
    }

    public function changeState($id){
        $tray = Tray::find($id);
        $tray->state = !$tray->state;
        $tray->save();

        return $this->successResponse($tray);
    }

    public function matchTrayFields($tray, $formAnswers){

        foreach ($formAnswers as $formAnswer) {

            /* entrada a bandeja */
            $in_fields_matched = 0;
            foreach(json_decode($tray->fields) as $field){

                $estructura = json_decode($formAnswer->structure_answer);
                // Filtrar que contenga el id del field buscado
                $tray_in = collect($estructura)->filter( function ($value, $key) use ($field) {
                    // si es tipo options, validar el valor del option
                    if($field->type == "options"){
                        if($value->id==$field->id){
                            foreach($field->value as $fieldValue){
                                if($value->value == $fieldValue->id){
                                    return 1;
                                }else{
                                    return 0;
                                }
                            }
                        }
                    }else{
                        // si es otro tipo validar que el valor no este vacio o nulo.
                        if($value->id==$field->id && !empty($value->value)){
                            return 1;
                        }else{
                            return 0;
                        }
                    }

                });

                if(count($tray_in)>=1){
                    $in_fields_matched++;
                }
            }
            
            if((count(json_decode($tray->fields))> 0) && ($in_fields_matched == count(json_decode($tray->fields)))){
                
                $tray->FormAnswers()->attach($formAnswer->id);
            }

        }
        
    }

}
