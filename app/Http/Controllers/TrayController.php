<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\Tray;
use App\Models\Section;
use Illuminate\Http\Request;
use App\Models\FormAnswersTray;
use App\Support\Collection;
use stdClass;

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

        if(!in_array('crm::admin', $data['rols'])){
            $data['rols'][] = 'crm::admin';
        }

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
        if($request->query('showall', 0) == 0){
            $trays = Tray::where('form_id', $id)->where('state', 1)->get();
        }else{
            $trays = Tray::where('form_id', $id)->get();
        }
        foreach($trays as $tray){
            if($tray->advisor_manage==1){
                $formAnswersTrays= FormAnswersTray::selectRaw('count(form_answers_trays.id) as NumAnswers')->where('form_answers_trays.tray_id',$tray->id)->join('rel_trays_users','form_answers_trays.id','=','rel_trays_users.form_answers_trays_id')->where('rel_trays_users.rrhh_id',auth()->user()->rrhh_id)->get();
            }else{
                $formAnswersTrays= FormAnswersTray::selectRaw('count(form_answers_trays.id) as NumAnswers')->where('form_answers_trays.tray_id',$tray->id)->get();
            }

            $tray->count=json_decode($formAnswersTrays[0])->NumAnswers;
        }

        //$trays = Tray::where('form_id', $id)->get();
        /*->leftJoin('form_answers_trays', 'trays.id', '=', 'form_answers_trays.tray_id');
        if($request->query('showall', 0) == 0)
        {
            $trays = $trays->where('state', 1)->having(DB::raw('count(tray_id)'), '>', 0);
        }
        $trays = $trays->selectRaw('trays.*, count(tray_id) as count')
            ->groupBy('trays.id', 'trays.name', "trays.form_id", "trays.fields", "trays.rols", "trays.state", "trays.created_at", "trays.updated_at", "trays.fields_exit", "trays.fields_table", "trays.advisor_manage", "trays.save_historic")->get();*/
        if(count($trays)==0) {
            return $this->successResponse([]);
        }

        // validar si el usuario actual puede visualizar trays dependiendo de su rol.
        $trays = $trays->filter(function($x){
            return count(array_intersect(auth()->user()->roles, json_decode($x->rols)));
        });

        $filters = array_map(function ($item) {
            return $item->id;
        }, json_decode(Form::find($id)->filters));

        return response()->json(['trays' => $trays, 'form_filter' => $filters]);
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

        if($tray==null) {
            return $this->errorResponse('No se encontro la bandeja',404);
        }

        return $this->successResponse($tray);
    }

    public function formAnswersByTray(Request $request, $id) {
        $sought = $request->sought;
        $filteredFields = $request->filteredFields;
        $tray = Tray::where('id',$id)->firstOrFail();
        $fieldsTable = collect(json_decode($tray->fields_table));
        $formsAnswers = FormAnswer::select(
            'form_answers.id',
            'form_answers.structure_answer',
            'form_answers.form_id',
            'form_answers.channel_id',
            'form_answers.rrhh_id',
            'form_answers.client_new_id'
        )->join('form_answers_trays', "form_answers.id", 'form_answers_trays.form_answer_id')
        ->join('trays', "trays.id", 'form_answers_trays.tray_id')
        ->where("trays.id", $id);

        if($tray->advisor_manage == 1){
            $formsAnswers = $formsAnswers->join('rel_trays_users','form_answers_trays.id','rel_trays_users.form_answers_trays_id')
            ->where('rel_trays_users.rrhh_id',auth()->user()->rrhh_id);
        }

        $formsAnswers = $formsAnswers->get();

        $formsAnswers->each(function (&$answer) use ($fieldsTable, $tray) {
            $new_structure_answer = array_map(function (&$item) use ($answer) {
                if (!isset($item->duplicated)) {
                    $select = $this->findSelect($answer->form_id, $item->id, $item->value);
                    if ($select) {
                        $item->value = $select;
                    }
                }
                return $item;
            }, json_decode($answer->structure_answer));

            $tableValues = [];

            $fieldsTable->each(function ($field) use ($new_structure_answer, &$tableValues) {
                $foundStructure = collect($new_structure_answer)->filter(function ($item) use ($field) {
                    return $item->id == $field->id;
                })->values();

                if (!empty($foundStructure)) {
                    $tableValues[] = $foundStructure;
                }
            });

            $answer->table_values = $tableValues;

            $structureAnswer = $answer->structure_answer ? json_decode($answer->structure_answer, true) : [];

            $formAnswersTray = (new FormAnswerTrayController)
                ->getFormAnswersTray($answer->id, $tray->id, $tray->form_id);

            $answer->structure_answer = isset($formAnswersTray) ?
                array_merge($structureAnswer, $formAnswersTray) :
                $structureAnswer;
        });

        if (trim($sought) != '') {
            $formsAnswers = $formsAnswers->filter(function ($answer) use ($filteredFields, $sought) {
                $found = false;
    
                foreach (json_decode($answer->structure_answer) as $field) {
                    if (in_array($field->id, $filteredFields) && str_contains($sought, strtolower((string) $field->value))) {
                        $found = true;
                        break;
                    }
                }
    
                return $found;
            });
        }

        return (new Collection($formsAnswers))->paginate(5);
    }

    public function changeState($id){
        $tray = Tray::find($id);
        $tray->state = !$tray->state;
        $tray->save();
        return $this->successResponse($tray);
    }

    /**
     * revisa la bandeja a ver si hay salida o entrada de la gestion a una bandeja
     * se ejecuta al crearse una bandeja nueva
     */
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
                            $validate = false;
                            foreach($field->value as $fieldValue){
                                if($value->value == $fieldValue->id){
                                    $validate = true;
                                }
                            }
                            if($validate == true){
                                return 1;
                            }else{
                                return 0;
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

    private function findSelect($form_id, $field_id, $value){
        $sections = Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first();
        if(!$sections)
        {
            return null;
        }
        $fields = $sections->fields;

        $field = collect(json_decode($fields))->filter(function($x) use ($field_id){
            return $x->id == $field_id;
        })->first();

        if(($field->controlType == 'dropdown' || $field->controlType == 'autocomplete' || $field->controlType == 'radiobutton')){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                return $x->id == $value;
            })->first()->name;
            return $field_name;
        } else {
            return null;
        }
    }


    public function sectionsDuplicated($idFormAnswer){
        $answer=json_decode(FormAnswer::where('id',$idFormAnswer)->first()->structure_answer);
        $seccionesDuplicar=[];
        $indicesDuplicar=[];
        foreach($answer as $fieldAnswer){
            if(isset($fieldAnswer->duplicated->Section)){
                $idSeccion=$fieldAnswer->duplicated->Section->id;
                $nameSeccion=$fieldAnswer->duplicated->Section->name;
                $hashSeccion=base64_encode($idSeccion.$nameSeccion);
                if(in_array($hashSeccion,$indicesDuplicar)){
                    $indice=array_search($hashSeccion,$indicesDuplicar);
                }else{
                    array_push($indicesDuplicar,$hashSeccion);
                    $indice=array_search($hashSeccion,$indicesDuplicar);
                }
                if(isset($seccionesDuplicar[$indice])){
                    $seccionesDuplicar[$indice]=$this->changeFieldSection($fieldAnswer,$seccionesDuplicar[$indice]);
                }else{
                    $seccionesDuplicar[$indice]=$this->createdDuplicatedSections($idSeccion,$nameSeccion);
                    $seccionesDuplicar[$indice]=$this->changeFieldSection($fieldAnswer,$seccionesDuplicar[$indice]);
                }
            }
        }
        return response()->json($seccionesDuplicar, '200');
    }


    private function createdDuplicatedSections($idSection,$nameDuplicatedSection){
        $formsSections=Section::select('name_section','type_section','fields','collapse')->where('id',$idSection)->first();
        $duplicatedSection=new stdClass();
        $duplicatedSection->id=time();
        $duplicatedSection->name_section=$nameDuplicatedSection;
        $duplicatedSection->collapse=$formsSections->collapse;
        $duplicatedSection->duplicate=0;
        $duplicatedSection->see=true;
        $duplicatedSection->fields=json_decode($formsSections->fields);
        return $duplicatedSection;
    }

    private function changeFieldSection($duplicatefield,$duplicatedSection){
        foreach($duplicatedSection->fields as $originalField){
            if($originalField->id==$duplicatefield->duplicated->idOriginal){
                $originalField->id=$duplicatefield->id;
                $originalField->key=$duplicatefield->key;
                $originalField->label=$duplicatefield->label;
                $originalField->duplicated=$duplicatefield->duplicated;
            }
        }
        return $duplicatedSection;
    }

}
