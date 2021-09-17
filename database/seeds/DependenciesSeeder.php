<?php

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\KeyValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DependenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sectionsNew = array();
        $formAnswersNew = array();
        $keyValues = array();
        $forms = Form::all();
        $totalForm = count($forms);
        
        foreach($forms as $form)
        {
            $this->command->info("Preparando datos del formularo de id: ".$form->id. " faltan ". $totalForm--);
            $dependencies = [];
            $fieldData = [];
            $fieldsNew = [];
            $idsAltered = [];
            foreach ($form->section as $section)
            {
                $fields = json_decode($section->fields);
                $fieldData = $this->getFieldData($fields, $fieldData, $section->id);
                foreach ($fields as $field)
                {
                    if(isset($field->dependencies))
                    {
                        foreach ($field->dependencies as $dependencie)
                        {
                            if(!isset($fieldData[$dependencie->idField]))
                            {
                                continue;
                            }
                            if(!isset($dependencies[$dependencie->idField]))
                            {
                                $dependencies[$dependencie->idField] = [];
                            }
                            $options = $this->findOption($fieldData[$dependencie->idField], $dependencie->name);
                            array_push($dependencies[$dependencie->idField], (Object)[
                                "idSon" => $field->id,
                                "labelSon" => $field->label,
                                "label" => $fieldData[$dependencie->idField]->label,
                                "options"=> $field->options,
                                "activators"=>[$options],
                                "field" => $field
                            ]);
                        }
                    }
                }
                $timestamp = time();
                $dependencieNewKey = null;
                //Crea 
                foreach ($dependencies as $idFather => $dependencie)
                {
                    foreach ($dependencie as  $keyDepend => $depend)
                    { 
                        foreach ($fieldsNew as $key => $fieldNew)
                        {
                            $dependencieNewKey = null;
                            if($fieldNew->datosAux->idFather == $idFather
                                && $fieldNew->type == $depend->field->type
                                && $fieldNew->controlType == $depend->field->controlType)
                            {
                                $dependencieNewKey = $key;
                                break;
                            }
                        }
                        if(!$dependencieNewKey && !isset($fieldsNew[$dependencieNewKey]))
                        {
                            $fieldNew = (Object)[
                                "id" => $timestamp++,
                                "type" => $depend->field->type,
                                "key" => $depend->field->key,
                                "controlType" => $depend->field->controlType,
                                "label" => $depend->field->label,
                                "value" => "",
                                "required" => $depend->field->required,
                                "canAdd"=> $depend->field->canAdd,
                                "minLength"=> $depend->field->minLength,
                                "maxLength"=> $depend->field->maxLength,
                                "inReport"=> $depend->field->inReport,
                                //"isClientInfo"=> $depend->field->isClientInfo,
                                "disabled"=> $depend->field->disabled,
                                "cols"=> $depend->field->cols,
                                "preloaded"=> $depend->field->preloaded,
                                //"isSon"=> $depend->field->isSon,
                                "dependencies"=> [],
                                "editRoles" => $depend->field->editRoles,
                                "seeRoles" => $depend->field->seeRoles,
                                "tooltip" => $depend->field->tooltip,
                                "options" => [],
                                "datosAux" =>(Object)[
                                    "idFather" => $idFather,
                                    "optionIdAux" => 1,
                                    "idsOld" =>[],
                                    "sectionId" => $fieldData[$idFather]->sectionId,
                                ]
                            ];
                            array_push($fieldsNew, $fieldNew);
                            $dependencieNewKey = array_key_last($fieldsNew);
                        }
                        foreach ($depend->options as &$option)
                        {
                            $option->idOld = isset($option->id)? $option->id: $option->Id;
                            $option->id = $fieldsNew[$dependencieNewKey]->datosAux->optionIdAux++;
                            if(!isset($depend->activators[0]->id))
                            {
                                continue;
                            }
                            if($depend->activators[0]->id == $option->idOld)
                            {
                                $activatorsNew = [];
                                $activatorsNew[0] = (Object)[];
                                $activatorsNew[0]->name = $option->name;
                                $activatorsNew[0]->idOld = $option->idOld;
                                $activatorsNew[0]->id = $option->id;
                            }
                        }
                        array_push($fieldsNew[$dependencieNewKey]->datosAux->idsOld, $depend->field->id);
                        $idsAltered[$depend->field->id] = $fieldsNew[$dependencieNewKey]->id;
                        $dependencieNew = (Object)[
                            "activators" => $depend->activators,
                            "idField" => $idFather,
                            "label" => $fieldData[$idFather]->label,
                            "options" => $depend->options,
                            "idFieldOld" => $depend->field->id,
                        ];
                        array_push($fieldsNew[$dependencieNewKey]->dependencies, $dependencieNew);
                        $fieldsNew[$dependencieNewKey]->options = array_merge($fieldsNew[$dependencieNewKey]->options, $depend->options);
                        unset($dependencies[$idFather][$keyDepend]);
                    }
                }
                //Actualizando los idField del padre en los hijos
                foreach ($fieldsNew as &$fieldNew)
                {
                    foreach ($fieldNew->dependencies as &$dependencieAux)
                    {
                        if(isset($idsAltered[$dependencieAux->idField]))
                        {
                            $dependencieAux->idField = $idsAltered[$dependencieAux->idField];
                        }
                    }
                }
            }

            foreach ($form->section as &$section)
            {
                foreach ($fieldsNew as $fieldNew)
                {
                    $fields = json_decode($section->fields);
                    //Removiendo los campos que cambiaron
                    foreach ($fields as $key => $field)
                    {
                        if(in_array($field->id, $fieldNew->datosAux->idsOld))
                        {
                            unset($fields[$key]);
                        }
                    }
                    //Agregando los nuevos campos
                    $fildsUpdate = array_values($fields);
                    if($section->id == $fieldNew->datosAux->sectionId)
                    {
                        $aux = (array)$fieldNew;
                        unset($aux["datosAux"]);
                        array_push($fildsUpdate, $aux);
                    }
                    $section->fields = json_encode($fildsUpdate);

                }
                $sectionNew = [
                    'id' => $section->id,
                    'form_id' => $section->form_id,
                    'name_section' => $section->name_section,
                    'type_section' => $section->type_section,
                    'fields' => $section->fields,
                    'collapse' => $section->collapse,
                    'duplicate' => $section->duplicate,
                    'state' => $section->state
                ];

                array_push($sectionsNew, $sectionNew);
            }

            foreach ($form->formAnswers as $formAnswer)
            {
                $structureAnswer = json_decode($formAnswer->structure_answer);
                foreach ($structureAnswer as &$answer)
                {
                    foreach ($fieldsNew as $fieldNew)
                    {
                        if(in_array($answer->id, $fieldNew->datosAux->idsOld))
                        {
                            foreach ($fieldNew->dependencies as $dependen)
                            {
                                if($dependen->idFieldOld == $answer->id)
                                {
                                    foreach ($dependen->options as $option)
                                    {
                                        if($option->idOld == $answer->value)
                                        {
                                            $answer->value = $option->id;
                                            if($fieldNew->preloaded)
                                            {
                                                $keyValue = [
                                                    'form_id' => $formAnswer->form_id,
                                                    'key' => $fieldNew->key,
                                                    'value' => $fieldNew->value,
                                                    'description' => "",
                                                    'field_id' => $fieldNew->id,
                                                    'client_new_id' => $formAnswer->client_new_id
                                                ];
                                                array_push($keyValues, $keyValue);
                                            }
                                        }
                                    }
                                }
                            }
                            $answer->id = $fieldNew->id;
                            $answer->key = $fieldNew->key;
                            $answer->label = $fieldNew->label;
                            $answer->preloaded = $fieldNew->preloaded;
                        }
                    }
                }
                $formAnswer->structure_answer = json_encode($structureAnswer);
                $formAnswerNew = [
                    'id' => $formAnswer->id,
                    'form_id' => $formAnswer->form_id,
                    'rrhh_id' => $formAnswer->rrhh_id,
                    'channel_id' => $formAnswer->channel_id,
                    'structure_answer' => $formAnswer->structure_answer,
                    "client_new_id" => $formAnswer->client_new_id,
                    "form_answer_index_data" => $formAnswer->form_answer_index_data,
                    "tipification_time" => $formAnswer->tipification_time
                ];
                array_push($formAnswersNew, $formAnswerNew);
            }
        }
        $insertQtd = 100;
        $sectionsNewChunk = array_chunk($sectionsNew, $insertQtd);
        $qtd = 0;
        foreach ($sectionsNewChunk as $sectionNewChunk)
        {
            
            $this->command->info("guardando $insertQtd sections, $qtd ya insertados, de un total de ".count($sectionsNew));
            DB::table('sections_new')->insert($sectionNewChunk);
            $qtd += 1000;
        }

        $formAnswersNewChunk = array_chunk($formAnswersNew, $insertQtd);
        $qtd = 0;
        foreach ($formAnswersNewChunk as $formAnswerNewChunk)
        {

            $this->command->info("guardando $insertQtd form_answer, $qtd ya insertados, de un total de ".count($formAnswersNew));
            DB::table('form_answer_new')->insert($formAnswerNewChunk);
            $qtd += $insertQtd;
        }

        $keyValuesChunk = array_chunk($keyValues, $insertQtd);
        $qtd = 0;
        foreach ($keyValuesChunk as $keyValueChunk)
        {
            $this->command->info("guardando $insertQtd KeyValue, $qtd ya insertados, de un total de ".count($keyValues));
            KeyValue::insert($keyValueChunk);
            $qtd += $insertQtd;
        }

        $this->command->info("Renombrando tablas");
        Schema::rename("form_answer", "form_answer_old");
        Schema::rename("form_answer_new", "form_answer");
        Schema::rename("sections", "sections_old");
        Schema::rename("sections_new","sections");
    }

    private function getFieldData($fields, $fieldData, $sectionId)
    {
        foreach ($fields as $field)
        {
            $fieldData[$field->id] = (object)[
                "label" => $field->label,
                "options" => $field->options,
                "sectionId" => $sectionId
            ];
        }
        return $fieldData;
    }

    private function findOption($fieldData, $name)
    {
        foreach ($fieldData->options as $option)
        {
            if(isset($option->name) && $option->name == $name)
            {
                return $option;
            }
        }
        return $fieldData;
    }
}