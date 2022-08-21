<?php

namespace App\Http\Controllers;

use App\Managers\ClientsManager;
use App\Models\Channel;
use App\Models\Directory;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\FormAnswerLog;
use App\Traits\FieldsForSection;
use Illuminate\Http\Request;
use stdClass;

class ExternalController extends Controller
{
    use FieldsForSection;
    
    public function uploadClientFromVideoChat(Request $request)
    {
        $this->validate($request, [
            'form_id' => 'required|integer|exists:forms,id',
            'fields'   => 'required|array',
            'rrhh_id' => 'required|integer',
            'canal' => 'required|string'
        ]);

        $formId = $request->form_id;
        
        $prechargables = $this->searchPrechargeFields($formId)->getData();
        $fileInfo['prechargables'] = [];
        
        foreach($prechargables->section as $section){
            foreach($section->fields as $field){
                if($field){
                    $prechargedField = new stdClass();
                    $prechargedField->id = $field->id;
                    $prechargedField->label = $field->label;
                    array_push($fileInfo['prechargables'], $prechargedField);
                }
            }
        }
        
        $fieldsLoad = $this->getSpecificFieldForSection($fileInfo['prechargables'], $formId);
        
        $answerFields = (Object)[];
        $formAnswerClient=[];
        
        foreach ($fileInfo['prechargables'] as $assign){
            foreach ($fieldsLoad as $key => $field) {
                if ($field->id == $assign->id && isset($request->fields[$field->id])) {
                    $fieldsLoad[$assign->label] = $field;
                    $data = $request->fields[$field->id];

                    unset($fieldsLoad[$key]);
                    $field->value = $data;
                    $answer = new stdClass();
                    $answer->in = [];

                    if(isset($field->isClientInfo) && $field->isClientInfo){
                        $answer->informationClient= (object)[
                            "id" => $field->id,
                            "value" => $field->value
                        ];
                        array_push($answer->in,'informationClient');
                    }

                    if(isset($field->client_unique) && $field->client_unique){
                        $answer->uniqueIdentificator = (Object)[
                            "id" => $field->id,
                            "key" => $field->key,
                            "preloaded" => $field->preloaded,
                            "label" => $field->label,
                            "isClientInfo" => $field->isClientInfo,
                            "client_unique" => $field->client_unique,
                            "value" => $field->value
                        ];
                        array_push($answer->in,'uniqueIdentificator');
                    }

                    if(isset($field->preloaded) && $field->preloaded){
                        $answer->preload=[
                            "id" => $field->id,
                            "key" => $field->key,
                            "value" => $field->value
                        ];
                        array_push($answer->in,'preload');
                    }

                    $answer->formAnswer = (Object)[
                        "id" => $field->id,
                        "key" => $field->key,
                        "preloaded" => $field->preloaded,
                        "label" => $field->label,
                        "isClientInfo" => $field->isClientInfo,
                        "client_unique" => isset($field->client_unique) ? $field->client_unique : false,
                        "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value,
                        "controlType" => $field->controlType,
                        "type" => $field->type
                    ];

                    $answer->formAnswerIndex = (Object)[
                        "id" => $field->id,
                        "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
                    ];

                    $answer->success=true;
                    $answer->Originalfield=$field;

                    foreach ($answer->in as $in) {
                        if (!isset($answerFields->$in)) {
                            $answerFields->$in = [];
                        }
                        
                        array_push($answerFields->$in, $answer->$in);
                    }
                    array_push($formAnswerClient, $answer->formAnswer);
                }
            }
        }

        $clientsManager = new ClientsManager;

        $data = [
            "form_id" => $formId,
            "unique_indentificator" => $answerFields->uniqueIdentificator[0],
        ];

        $client = $clientsManager->findClient($data);

        $data['information_data'] = $answerFields->informationClient;

        $client = $clientsManager->updateOrCreateClient($data);

        if(isset($client->id)){
            $saveDirectories = $this->addToDirectories($formAnswerClient, $formId, $client->id, $data['information_data'], $request->rrhh_id);
        }

        $structureAnswer = [];
        $formAnswerIndexData = [];
        $formAnswerAux = [];

        foreach ($formAnswerClient as $answer) {
            $formAnswerIndexData[] = [
                'id' => $answer->id,
                'value' => $answer->value
            ];
            $formAnswerAux[$answer->id] = $answer->value;
            unset($answer->type);
            unset($answer->controlType);
            $structureAnswer[] = $answer;
        }

        $formAnswer = FormAnswer::formFilter($formId)->clientFilter($client->id)->where('status', 1)->first();
        $channel = Channel::nameFilter($request->canal)->first();

        if (!$formAnswer) {
            $formAnswer = FormAnswer::create([
                'structure_answer' => json_encode($structureAnswer),
                'form_id' => $formId,
                'channel_id' => $channel->id,
                'rrhh_id' => $request->rrhh_id,
                'client_new_id' => $client->id,
                'form_answer_index_data' => json_encode($formAnswerIndexData),
            ]);
            $formAnswer->channel_id = $channel->id;
        } else {
            $formAnswer->channel_id = $channel->id;
            $formAnswer->structure_answer = json_encode($structureAnswer);
            $formAnswer->save();
        }

        $log = new FormAnswerLog();
        $log->form_answer_id = $formAnswer->id;
        $log->structure_answer = $formAnswer->structure_answer;
        $log->rrhh_id = $formAnswer->rrhh_id;
        $log->save();

        return response()->json([
            'client_id' => $client->id
        ], 200);
    }

    private function addToDirectories(array $data,int $formId,int $clientNewId, array $indexForm, $rrhhId){
        $newDirectory = Directory::updateOrCreate([
            'form_id' => $formId,
            'client_new_id' => $clientNewId,
            'data' => json_encode($data)

        ],[
            'rrhh_id' => $rrhhId,
            'form_index' => json_encode($indexForm)
        ]);

        return $newDirectory;
    }

    public function searchPrechargeFields($id)
    {
        $formsSections = Form::where('id', $id)
            ->with('section')
            ->select('*')
            ->first();
        $formsSections->seeRoles = json_decode($formsSections->seeRoles);
        $formsSections->filters = json_decode($formsSections->filters);
        for ($i = 0; $i < count($formsSections->section); $i++) {
            unset($formsSections->section[$i]['created_at']);
            unset($formsSections->section[$i]['updated_at']);
            unset($formsSections->section[$i]['form_id']);
            // $formsSections->section[$i]['fields'] = json_decode($formsSections->section[$i]['fields']);
            $fields = collect(json_decode($formsSections->section[$i]['fields']));

            if($i==0){
                for($j=0;$j<count($fields);$j++){
                    if($fields[$j]->preloaded == false){
                        unset($fields[$j]);
                    }
                }
            }else{
                $fields = $fields->filter(function($x){
                            return $x->preloaded == true;
                          });
            }
            $formsSections->section[$i]['fields'] = array_values($fields->toArray());
        }

        return response()->json($formsSections);
    }
}
