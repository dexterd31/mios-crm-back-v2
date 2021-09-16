<?php

use Illuminate\Database\Seeder;
use App\Models\Form;

class DependenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $forms = Form::where("id", 5)->get();
        foreach($forms as $form)
        {
            $dependencies = [];
            $fieldData = [];
            $fieldsNew = [];
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
                foreach ($dependencies as $idFather => $dependencie)
                {
                    foreach ($dependencie as $depend)
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
                            $option->idOld = $option->id;
                            $option->id = $fieldsNew[$dependencieNewKey]->datosAux->optionIdAux++;
                            if($depend->activators[0]->id == $option->idOld)
                            {
                                $depend->activators[0]->idOld = $option->idOld;
                                $depend->activators[0]->id = $option->id;
                            }
                        }
                        array_push($fieldsNew[$dependencieNewKey]->datosAux->idsOld, $depend->field->id);
                        $dependencie = (Object)[
                            "activators" => $depend->activators,
                            "idField" => $idFather,
                            "label" => $fieldData[$idFather]->label,
                            "options" => $depend->options,
                            "idFieldOld" => $depend->field->id,
                        ];
                        array_push($fieldsNew[$dependencieNewKey]->dependencies, $dependencie);
                    }
                }
            }

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
                    if($section->id == $fieldNew->datosAux->sectionId)
                    {
                        $aux = $fieldNew;
                        //unset($aux->datosAux);
                        array_push($fields, $aux);
                    }
                    \Log::info($fields);
                }
            }
            // \Log::info(json_encode($fieldData, JSON_PRETTY_PRINT));
            // \Log::info(json_encode($dependencies, JSON_PRETTY_PRINT));
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
                                        }
                                    }
                                }
                            }
                            $answer->id = $fieldNew->id;
                            $answer->key = $fieldNew->key;
                            // $answer->label = $fieldNew->label;
                            // $answer->preloaded = $fieldNew->preloaded;
                        }
                    }
                }
                $formAnswer->structure_answer = json_encode($structureAnswer);
                \Log::info($formAnswer->structure_answer);
                //$formAnswer->save();
            }
            $form->formAnswers;
            //\Log::info(json_encode($fieldsNew, JSON_PRETTY_PRINT));
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

// 1630445772796=[
//     {
//         "id"=1630445864052,
//         "options"=[
//             {
//                 "id": 1,
//                 "name": null
//             }
//         ],
//         "Label"="",
//         "name" = ""

//     },
//     {
//         "id"=1630446261326,
//         "options"=[
//             {
//                 "id": 1,
//                 "name": null
//             }
//         ],
//         "Label"=""

//     }
// ];