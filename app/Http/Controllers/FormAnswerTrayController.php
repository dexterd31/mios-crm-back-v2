<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormAnswersTray;
use App\Models\Section;
use App\Models\getFormAnswersTray;

class FormAnswerTrayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($formAnswerId, $trayId)
    {
        return FormAnswersTray::where("form_answer_id", $formAnswerId)->where("tray_id", $trayId)->get();
    }

    public function getFormAnswersTray($idFormAnswer, $idTray, $formId)
    {
        $formAnswersTray = FormAnswersTray::where("tray_id", $idTray)->where("form_answer_id", $idFormAnswer)->where("lastAnswersTrays", 1)->first();
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
