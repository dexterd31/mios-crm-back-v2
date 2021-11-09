<?php

namespace App\Http\Controllers;

use App\Models\FormAnswerLog;
use App\Models\Tray;
use App\Models\FormAnswersTray;
use App\Models\Section;

class FormAnswerTrayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($formAnswerId, $trayId)
    {
        $historicAnswer = [];
        $formAnswerLogs = FormAnswerLog::where('form_answer_id',$formAnswerId)->get('structure_answer')->toArray();
        $traysSaveHistoric = Tray::where('id',$trayId)->first('save_historic');
        $saveHistoric = json_decode($traysSaveHistoric)->save_historic;
        if($saveHistoric === null){
            $response = new \stdClass();
            $response->data = "no se encontraron historicos asociados";
            return response()->json($response,204);
        }
        foreach ($formAnswerLogs as $structureAnswer){
            foreach (json_decode($structureAnswer['structure_answer']) as $answer){
                if(array_search($answer->id,json_decode($saveHistoric))!== false){
                    array_push($historicAnswer,$answer);
                }
            }
        }
        return $historicAnswer;
    }

    public function getFormAnswersTray($idFormAnswer, $idTray, $formId)
    {
        $formAnswersTray = FormAnswersTray::where("tray_id", $idTray)->where("form_answer_id", $idFormAnswer)->first();
        $structureAnswerTray = json_decode($formAnswersTray->structure_answer_tray);
        $answerTray = [];
        if(!isset($structureAnswerTray))
        {
            return [];
        }
        $sections = Section::where("form_id", $formId)->get();
        foreach($structureAnswerTray as $answer)
        {
            foreach ($sections as $section)
            {
                $fields = json_decode($section->fields);
                foreach ($fields as $field)
                {
                    if($answer->id ==  $field->id && isset($field->tray))
                    {
                        foreach ($field->tray as $tray)
                        {
                            if($tray->id == $idTray)
                            {
                                array_push($answerTray, $answer);
                            }
                        }

                    }
                }
            }
        }
        return $answerTray;
    }
}
