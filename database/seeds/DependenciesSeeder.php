<?php

use App\Models\ClientNew;
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
        $clientsNew = array();
        $clientsNewAux = [];
        $idClientNew = 1;
        $keyDataClient = array(
            "firstName" => "first_name",
            "middleName" => "middle_name",
            "lastName" => "first_lastname",
            "secondLastName" => "second_lastname",
            "document_type_id" => "document_type_id",
            "document" => "document",
            "phone" => "phone",
            "email" => "email"
        );
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
                if($section->type_section == 1)
                {
                    $fields = json_decode($section->fields);
                    foreach ($fields as &$field)
                    {
                        if(array_key_exists($field->key, $keyDataClient))
                        {
                            $field->isClientInfo = true;
                            if($field->key == "document")
                            {
                                $form->fields_client_unique_identificator = json_encode([$field]);
                            }
                        }
                    }
                    $section->fields = json_encode($fields);
                }
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
                $formAnswerIndexData = [];
                $structureAnswer = json_decode($formAnswer->structure_answer);
                $clientData = [];
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
                            $answer->preloaded = $fieldNew->preloaded;
                            $answer->id = $fieldNew->id;
                            $answer->key = $fieldNew->key;
                            $answer->label = $fieldNew->label;
                        }
                    }
                    if(array_key_exists($answer->key, $keyDataClient))
                    {
                        $answer->isClientInfo = true;
                        if($answer->key == "document")
                        {
                            $answer->preloaded = true;
                            $answer->client_unique = true;
                            $clientUnique = [(Object)[
                                "label" => isset($answer->label) ? $answer->label : "no se encuntra label en el formAnswer" ,
                                "preloaded" => true,
                                "id" => $field->id,
                                "key" => $answer->key,
                                "value" => $answer->value,
                                "isClientInfo" => true,
                                "client_unique" => true
                            ]];
                        }
                    }
                    array_push($formAnswerIndexData, [
                        "id"=> $answer->id,
                        "value"=> $answer->value
                    ]);
                    if(isset($answer->preloaded) && $answer->preloaded)
                    {   
                        array_push($clientData, [
                            "id" => $answer->id,
                            "value" => $answer->value,
                        ]);
                    }
                }

                $clientNewAux = (Object)["form_id" => $formAnswer->form_id, "client_id" => $formAnswer->client_id ];
                if(!in_array($clientNewAux, $clientsNewAux) && $clientUnique)
                {
                    array_push($clientsNew, [
                        "id" => $idClientNew++,
                        "information_data" => json_encode($clientData),
                        "unique_indentificator" => json_encode($clientUnique),
                        "form_id" => $formAnswer->form_id,
                    ]);
                    $clientsNewAux[$idClientNew] = $clientNewAux;
                }

                $formAnswer->structure_answer = json_encode($structureAnswer);
                $formAnswerNew = [
                    'id' => $formAnswer->id,
                    'form_id' => $formAnswer->form_id,
                    'rrhh_id' => $formAnswer->rrhh_id,
                    'channel_id' => $formAnswer->channel_id,
                    'structure_answer' => $formAnswer->structure_answer,
                    "client_new_id" => $formAnswer->client_new_id,
                    "client_id" => $formAnswer->client_id,
                    "form_answer_index_data" => json_encode($formAnswerIndexData),
                    "tipification_time" => $formAnswer->tipification_time
                ];

                array_push($formAnswersNew, $formAnswerNew);
            }
            $filters = json_decode($form->filters);
            foreach ($filters as $filter)
            {
                if(array_key_exists($filter->key, $keyDataClient))
                {
                    $filter->isClientInfo = true;
                    if($filter->key == "document")
                    {
                        $filter->preloaded = true;
                        $filter->client_unique = true;
                        
                    }
                }
            }
            $form->filters = json_encode($filters);

            $form->save();
        }
        $insertQtd = 2;
        $sectionsNewChunk = array_chunk($sectionsNew, $insertQtd);
        $qtd = 0;
        foreach ($sectionsNewChunk as $sectionNewChunk)
        {
            
            $this->command->info("guardando $insertQtd sections, $qtd ya insertados, de un total de ".count($sectionsNew));
            //DB::table('sections_new')->insert($sectionNewChunk);
            $qtd += $insertQtd;
        }

        $formAnswersNewChunk = array_chunk($formAnswersNew, $insertQtd);
        $qtd = 0;
        foreach ($formAnswersNewChunk as $formAnswerNewChunk)
        {

            $this->command->info("guardando $insertQtd form_answer, $qtd ya insertados, de un total de ".count($formAnswersNew));
            //DB::table('form_answer_new')->insert($formAnswerNewChunk);
            $qtd += $insertQtd;
        }

        $keyValuesChunk = array_chunk($keyValues, $insertQtd);
        $qtd = 0;
        foreach ($keyValuesChunk as $keyValueChunk)
        {
            $this->command->info("guardando $insertQtd KeyValue, $qtd ya insertados, de un total de ".count($keyValues));
           // KeyValue::insert($keyValueChunk);
            $qtd += $insertQtd;
        }

        $clientsNewChunk = array_chunk($clientsNew, $insertQtd);
        $qtd = 0;
        foreach ($clientsNewChunk as $clientNewChunk)
        {
            $this->command->info("guardando $insertQtd clientNew, $qtd ya insertados, de un total de ".count($clientsNew));
            //ClientNew::insert($clientNewChunk);
            $qtd += $insertQtd;
        }

        $qtd = 0;
        $this->command->info("Actualizando directories");
        $clientsNewAuxChunk = array_chunk($clientsNewAux, $insertQtd);
        foreach ($clientsNewAuxChunk as $clientNewAuxChunk)
        {
            $qtd++;
            $this->command->info("Actualizando $insertQtd directories, $qtd ya insertados, de un total de ".count($clientsNewAux));
            $this->updateClientId($clientNewAuxChunk, "directories");
            $this->command->info("Actualizando $insertQtd key_values, $qtd ya insertados, de un total de ".count($clientsNewAux));
            $this->updateClientId($clientNewAuxChunk, "key_values");
            $this->command->info("Actualizando $insertQtd form_answers, $qtd ya insertados, de un total de ".count($clientsNewAux));
            $this->updateClientId($clientNewAuxChunk, "form_answers");
        }

        $this->command->info("Renombrando tablas");
        Schema::rename("form_answers", "form_answer_old");
        Schema::rename("form_answer_new", "form_answers");
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

    private function updateClientId($clientNewAux, $table)
    {
        $cases = "";
        $caseInicio = "UPDATE $table SET client_new_id = case ";
        foreach ($clientNewAux as $idClientNew => $client)
        {
            $cases .= " WHEN client_id = ".$client->client_id." and form_id = ".$client->form_id." then ".$idClientNew;
        }
        $sql = $caseInicio.$cases."0"." END;";
        DB::select($sql);
    }
}