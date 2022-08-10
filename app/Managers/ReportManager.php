<?php

namespace App\Managers;

use App\Exports\FormReportExport;
use App\Models\Section;
use App\Models\Tray;
use App\Services\NotificationsService;
use App\Services\RrhhService;
use App\Traits\FindAndFormatValues;
use Helpers\MiosHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportManager
{
    use FindAndFormatValues;
    
    public function consultReportData(array $data, $rrhhIdToNotify)
    {
        ini_set('memory_limit', '1000M');
        set_time_limit(0);

        $char="";

        $trayHistoric = Tray::select('id')->where('form_id', $data['formId'])->whereNotNull('save_historic')->get();

        $formAnswers = DB::table('form_answer_logs')
        ->join('form_answers', 'form_answer_logs.form_answer_id', 'form_answers.id')
        ->where('form_answers.form_id', $data['formId'])
        ->where('form_answers.tipification_time', '!=', 'upload')
        ->whereBetween('form_answer_logs.updated_at', ["{$data['date1']} 00:00:00", "{$data['date2']} 00:00:00"])
        ->orWhereBetween('form_answers.updated_at', ["{$data['date1']} 00:00:00", "{$data['date2']} 00:00:00"])
        ->select('form_answer_logs.form_answer_id as id', 'form_answer_logs.structure_answer', 'form_answers.created_at', 'form_answer_logs.updated_at','form_answer_logs.rrhh_id as id_rhh', 'form_answers.tipification_time')
        ->get();

        if ($formAnswers->count()) {
            //Agrupamos los id_rrhh del usuario en un arreglo
            $userIds = (new MiosHelper)->getArrayValues('id_rhh', $formAnswers);

            $adviserInfo = [];

            collect($userIds)->chunk(100)->each(function ($rrhhIds) use (&$adviserInfo) {
                $useString = implode(',', array_values(array_unique($rrhhIds->toArray())));
                $usersInfo = (new RrhhService)->fetchUsers($useString);
                
                //Traemos los datos de rrhh de los usuarios
                //Organizamos la información del usuario en un array asociativo con la información necesaria
                foreach($usersInfo as $info){
                    if(in_array($info->id, $rrhhIds->toArray())){
                        if(!isset($adviserInfo[$info->id])){
                            $adviserInfo[$info->id] = $info;
                        }
                    }
                }
            });

            $inputReport = [];
            $titleHeaders = ['Id'];
            $dependencies = [];
            $r = 0;
            $rows = [];
            $plantillaRespuestas = [];

            //Verificamos cuales son los campos que deben ir en el reporte o que su elemento inReport sea true
            $sections = Section::select('fields')->where("form_id", $data['formId'])->get();

            $plantillaRespuestas['id'] = $char;
            foreach ($sections as $section) {
                foreach (json_decode($section->fields) as $input) {
                    if ($input->inReport) {
                        if (count($input->dependencies)) {
                            if (isset($dependencies[$input->label])) {
                                array_push($dependencies[$input->label], $input->id);
                            } else {
                                $dependencies[$input->label] = [$input->id];
                                array_push($titleHeaders, $input->label);
                                array_push($inputReport, $input);
                                $plantillaRespuestas[$input->label] = $char;
                            }

                            $input->dependencies[0]->report = $input->label;
                        } else {
                            array_push($titleHeaders, $input->label);
                            array_push($inputReport, $input);
                            $plantillaRespuestas[$input->id] = $char;
                        }
                    }
                }
            }

            $plantillaRespuestas['user'] = $char;
            $plantillaRespuestas['docuser'] = $char;
            $plantillaRespuestas['created_at'] = $char;
            $plantillaRespuestas['updated_at'] = $char;
    
            foreach ($formAnswers as $answer) {
                $respuestas = $plantillaRespuestas;
                $respuestas['id'] = $answer->id;
                //Evaluamos los campos que deben ir en el reporte contra las respuestas
                foreach ($inputReport as $input) {
                    foreach (json_decode($answer->structure_answer) as $field) {
                        if (isset($input->dependencies[0]->report)) {
                            if (in_array($field->id,$dependencies[$input->dependencies[0]->report])) {
                                if (isset($field->value)) {
                                    $select = $this->findAndFormatValues($data['formId'], $field->id, $field->value);
                                    if ($select->valid && isset($select->name)) {
                                        $respuestas[$input->dependencies[0]->report] = $select->name;
                                    }else{
                                        $respuestas[$input->dependencies[0]->report] = $select->value ?? $select->message;
                                    }
                                }

                                break;
                            }
                        } else if ($field->id==$input->id) {
                            $select = $this->findAndFormatValues($data['formId'], $field->id, $field->value);
                            if ($select->valid && isset($select->name)) {
                                $respuestas[$input->id] = $select->name;
                            } elseif ($select->valid && isset($select->value)) {
                                $respuestas[$input->id] = $select->value;
                            } else {
                                $respuestas[$input->id] = json_encode($select);
                            }
                            break;
                        }else if($field->key==$input->key){
                            $select = $this->findAndFormatValues($data['formId'], $input->id, $field->value);
                            if($select->valid && isset($select->name)){
                                $respuestas[$input->id] = $select->name;
                            } else {
                                $respuestas[$input->id] = json_encode($select);
                            }
                            break;
                        }
                    }
                }

                $respuestas['user'] = $char;
                $respuestas['docuser'] = $char;

                if (isset($adviserInfo[$answer->id_rhh]->name)) {
                    $respuestas['user'] = $adviserInfo[$answer->id_rhh]->name;
                    $respuestas['docuser'] = $adviserInfo[$answer->id_rhh]->id_number;
                }

                if (gettype($answer->created_at) == 'object') {
                    $respuestas['created_at'] = Carbon::parse($answer->created_at->format('c'))->setTimezone('America/Bogota');
                    $respuestas['updated_at'] = Carbon::parse($answer->updated_at->format('c'))->setTimezone('America/Bogota');
                } else {
                    $respuestas['created_at'] = $answer->created_at;
                    $respuestas['updated_at'] = $answer->updated_at;
                }

                if (isset($data['include_tipification_time']) && $data['include_tipification_time']) {
                    $chronometer = $answer->tipification_time;
                    if ($chronometer != "upload") {
                        $tipification_time = explode(':', $chronometer);
                        if (count($tipification_time) <= 2) {
                          $tipification_time[2] = $tipification_time[1];
                          $tipification_time[1] = strlen($tipification_time[0]) >= 2 ? $tipification_time[0] : "0" . $tipification_time[0];
                          $tipification_time[2] = strlen($tipification_time[2]) >= 2 ? $tipification_time[2] : "0" . $tipification_time[2];
                          $tipification_time[0] = '00';
                        } else {
                          $tipification_time[0] = strlen($tipification_time[0]) >= 2 ? $tipification_time[0] : "0" . $tipification_time[0];
                          $tipification_time[1] = strlen($tipification_time[1]) >= 2 ? $tipification_time[1] : "0" . $tipification_time[1];
                          $tipification_time[2] = strlen($tipification_time[2]) >= 2 ? $tipification_time[2]: "0" . $tipification_time[2];
                        }
                
                        $chronometer = implode(":", $tipification_time);
                    }
                    
                    $respuestas['tipification_time'] = $chronometer;
                }
                $rows[$r]=$respuestas;
                $r++;
            }

            array_push($titleHeaders,'Asesor','Documento Asesor','Fecha de creación','Fecha de actualización');

            if(isset($data['include_tipification_time']) && $data['include_tipification_time']){
                array_push($titleHeaders,'Tiempo de tipificación');
            }

            $fileName = Carbon::now('America/Bogota')->getTimestamp();
            (new FormReportExport($rows, $titleHeaders))->store("reports/$fileName.xlsx");

            (new NotificationsService)->sendNotification('Reportes',"/mios/crm/forms/report-download/$fileName", $rrhhIdToNotify, 'Tu reporte esta disponible, descarga dando click aquí.');
        } else {
            (new NotificationsService)->sendNotification('Reportes','/mios/ciu', $rrhhIdToNotify, 'No se encontraron registros para crear el reporte.');
        }
    }
}