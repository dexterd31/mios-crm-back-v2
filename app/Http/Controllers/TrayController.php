<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Managers\TrafficTrayManager;
use App\Models\FormAnswer;
use App\Models\Tray;
use App\Models\Section;
use App\Models\FormAnswersTray;
use App\Support\Collection;
use Illuminate\Http\Request;
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
    public function store(Request $request, TrafficTrayManager $trafficTrayManager)
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
        if(isset($data['traffic'])){
            //creación de semaforización
            $data['tray_id'] = $tray->id;
            $trafficTrayManager->newTrafficTray($data);
        }
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

            $tray->count = json_decode($formAnswersTrays[0])->NumAnswers;
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
            return response()->json(['trays' => [], 'form_filter' => []]);
        }

        // validar si el usuario actual puede visualizar trays dependiendo de su rol.
        $trays = $trays->filter(function($x){
            return count(array_intersect(auth()->user()->roles, json_decode($x->rols)));
        });

        $filters = array_map(function ($item) {
            return $item->id;
        }, json_decode(Form::find($id)->filters));

        $auxArray = [];
        foreach ($trays as $tray) {
            $auxArray[] = $tray;
        }

        return response()->json(['trays' => $auxArray, 'form_filter' => $filters]);
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

    /**
     * Retorna los datos solicitados de una bandeja.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Request $request
     * @param integer $id - Identificador de la bandeja
     * @return Illuminate\Http\Response
     */
    public function formAnswersByTray(Request $request, int $id) {
        $sought = strtolower($request->sought);
        $filteredFields = $request->filteredFields ?? [];
        $columnToSort = $request->columnToSort;
        $columnToSort = isset($request->orientation) && $request->orientation == '' ? '' : $request->columnToSort;
        $orientation = isset($request->orientation) ? $request->orientation : 'DESC';
        $orientation = $orientation == '' || is_null($orientation) ? 'DESC' : $orientation;
        $tray = Tray::where('id',$id)->firstOrFail();
        $fieldsTable = collect(json_decode($tray->fields_table));

        $formsAnswers = FormAnswer::select(
            'form_answers.id',
            'form_answers.structure_answer',
            'form_answers.form_id',
            'form_answers.channel_id',
            'form_answers.rrhh_id',
            'form_answers.client_new_id'
        )->orderBy('form_answers.updated_at', $orientation)->join('form_answers_trays', "form_answers.id", 'form_answers_trays.form_answer_id')
        ->join('trays', "trays.id", 'form_answers_trays.tray_id')
        ->where("trays.id", $id);

        if($tray->advisor_manage == 1){
            $formsAnswers = $formsAnswers->join('rel_trays_users','form_answers_trays.id','rel_trays_users.form_answers_trays_id')
            ->where('rel_trays_users.rrhh_id',auth()->user()->rrhh_id);
        }

        $formsAnswers = $formsAnswers->get();

        $formsAnswers->each(function (&$answer) use ($fieldsTable, $tray) {
            if(isset($tray->trafficConfig)){
                $trafficTrayManager = app(TrafficTrayManager::class);
                $trafficTrayState = $trafficTrayManager->getTrafficTrayLog($tray->trafficConfig->id,$answer->id);
                if($trafficTrayState){
                    $answer->trafficTray = json_decode($trafficTrayState->data);
                }
            }

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

            $fieldsTable->each(function ($field) use ($new_structure_answer, &$tableValues, $tray, $answer) {
                if(isset($tray->trafficConfig)){
                    $trafficTrayManager = app(TrafficTrayManager::class);
                    $trafficTrayManager->validateTrafficTrayStatus($answer->id,$tray->trafficConfig);
                }
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
            $formsAnswers = $this->answersFilter($formsAnswers, $filteredFields, $sought);
        }

        if ($columnToSort != '' && !is_null($columnToSort)) {
            $formsAnswers = $this->answersSort($formsAnswers, $columnToSort, $orientation);
        }

        return (new Collection($formsAnswers))->paginate(5);
    }

    /**
     * Fitra las respuestas los datos de la bandeja.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Eloquent\Collection $formsAnswers
     * @param array $filteredFields - Identificadores de los campos por los cuales se va a filtrar.
     * @param string $sought - Valor con el cual se buscan las coincidencias.
     * @return Illuminate\Database\Eloquent\Collection
     */
    private function answersFilter($formsAnswers, array $filteredFields, string $sought)
    {
        return $formsAnswers->filter(function ($answer) use ($filteredFields, $sought) {
            $found = false;
            
            foreach ($answer->structure_answer as $field) {
                if (in_array($field['id'], $filteredFields)) {
                    $found = str_contains(strtolower((string) $field['value']), $sought);
                    if ($found) break;
                }
            }

            return $found;
        });
    }

    /**
     * Ordena los datos de la bandeja por la columna seleccionada.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Eloquent\Collection $formsAnswers
     * @param string $columnToSort - Columna por la cual se va a ordenar
     * @param string $orientation - Si el orden es asendende (ASC) o descendente (DESC)
     * @return array
     */
    private function answersSort($formsAnswers, string $columnToSort, string $orientation) : array
    {
        $formsAnswers = $formsAnswers->toArray();
        
        usort($formsAnswers, function ($answerA, $answerB) use ($columnToSort, $orientation) {
            $isNumeric = false;
            $values = [];
            $answers = [$answerA, $answerB];

            if ($orientation == 'DESC') {
                $answers = [$answerB, $answerA];
            }

            foreach ($answers as $answer) {
                foreach ($answer['structure_answer'] as $field) {
                    if ($field['label'] == $columnToSort) {
                        $isNumeric = is_numeric($field['value']);
                        $values[] = $field['value'];
                    }
                }
            }

            if ($isNumeric) {
                if ($orientation == 'DESC') {
                    $result = $values[0] < $values[1] ? 1 : -1;
                } 
                $result = $values[0] < $values[1] ? -1 : 1;
            } else {
                $result = strcasecmp(...$values);
            }

            return $result;
        });

        return $formsAnswers;
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
