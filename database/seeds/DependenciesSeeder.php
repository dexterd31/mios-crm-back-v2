<?php

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\KeyValue;

class DependenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $forms = Form::all();
        foreach($forms as $form)
        {
            $dependencies = [];
            $fieldData = [];
            $fieldsNew = [];
            foreach ($form->section as $section)
            {
                $fields = json_decode($section->fields);
                //\Log::info($fields);
                $fieldData = $this->getFieldData($fields, $fieldData, $section->id);
                foreach ($fields as $field)
                {
                    if(isset($field->dependencies))
                    {
                        foreach ($field->dependencies as $dependencie)
                        {
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
                            if($depend->activators[0]->id == $option->idOld)
                            {
                                $depend->activators[0]->idOld = $option->idOld;
                                $depend->activators[0]->id = $option->id;
                            }
                        }
                        array_push($fieldsNew[$dependencieNewKey]->datosAux->idsOld, $depend->field->id);
                        $dependencieNew = (Object)[
                            "activators" => $depend->activators,
                            "idField" => $idFather,
                            "label" => $fieldData[$idFather]->label,
                            "options" => $depend->options,
                            "idFieldOld" => $depend->field->id,
                        ];
                        array_push($fieldsNew[$dependencieNewKey]->dependencies, $dependencieNew);
                        unset($dependencies[$idFather][$keyDepend]);
                    }
                }
            }

            //\Log::info(json_encode($fieldsNew, JSON_PRETTY_PRINT));
            foreach ($form->section as $section)
            {
                foreach ($fieldsNew as $fieldNew)
                {
                    $fields = json_decode($section->fields);
                    foreach ($fields as $key => $field)
                    {
                        if(in_array($field->id, $fieldNew->datosAux->idsOld))
                        {
                            unset($fields[$key]);
                        }
                    }
                    $fields = array_values($fields);
                    if($section->id == $fieldNew->datosAux->sectionId)
                    {
                        $aux = (array)$fieldNew;
                        unset($aux["datosAux"]);
                        array_push($fields, $aux);
                    }
                    //\Log::info($fields);
                    $section->fields = json_encode($fields);
                    $section->save();
                }
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
                                                KeyValue::whereIn("field_id", $fieldNew->datosAux->idsOld)
                                                    ->where("form_id", $form->id)    
                                                    ->where("value", $option->idOld)
                                                    ->update(['value' => $answer->value, "field_id" => $fieldNew->id]);
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
                $formAnswer->save();
            }
        }
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