<?php

use App\Models\ClientNew;
use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\KeyValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DependenciesSeeder extends Seeder
{

    public static $QTD_INSERT_REGISTER = 25;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clientsNewAux2 = [];
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
        $forms = Form::all();
        $totalForm = count($forms);
        $this->command->info("Creando array values");
        $keyValues = [];

        $this->command->info("key Values creados: ".count($keyValues));
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
                                "isSon"=> true,
                                "dependencies"=> [],
                                "editRoles" => $depend->field->editRoles,
                                "seeRoles" => $depend->field->seeRoles,
                                "tooltip" => $depend->field->tooltip,
                                "options" => [],
                                "datosAux" =>(Object)[
                                    "idFather" => $idFather,
                                    "optionIdAux" => 1,
                                    "idsOld" =>[],
                                    "sectionId" => $section->id,
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
                foreach ($fieldsNew as $keyFields => $fieldNew)
                {
                    foreach ($fieldNew->dependencies as $keyEependencie => $dependencieAux)
                    {
                        if(isset($idsAltered[$dependencieAux->idField]))
                        {
                            $fieldsNew[$keyFields]->dependencies[$keyEependencie]->idField = $idsAltered[$dependencieAux->idField];
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
                                $field->client_unique=true;
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
                        $isSon = false;
                        if(isset($field->isSon) && $field->isSon)
                        {
                            $isSon = true;
                        }
                        $field->isSon = $isSon;

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
                    'state' => $section->state,
                    'created_at' => $section->created_at,
                    'updated_at' => date('Y-m-d H:i:s'),
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
                                                    'client_new_id' => $formAnswer->client_new_id,
                                                    'client_id' => $formAnswer->client_id,
                                                    'created_at' => date('Y-m-d H:i:s'),
                                                    'updated_at' => date('Y-m-d H:i:s'),
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
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $clientsNewAux[$idClientNew] = $clientNewAux;

                    $clientNewAux2Index = $formAnswer->form_id.":".$formAnswer->client_id;
                    $clientsNewAux2[$clientNewAux2Index] = (Object)[
                        "form_id" => $formAnswer->form_id,
                        "client_id" => $formAnswer->client_id,
                        "client_new_id" => $formAnswer->client_new_id,
                    ];
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
                    "tipification_time" => $formAnswer->tipification_time,
                    'created_at' => $formAnswer->created_at,
                    'updated_at' => date('Y-m-d H:i:s'),
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

        $this->command->info("Agregando client_id_new en formAnswersNew");
        foreach ($formAnswersNew as &$formAnswerNew)
        {
            $clientOld = (Object)["form_id" => $formAnswerNew["form_id"], "client_id" => $formAnswerNew["client_id"]];
            $idClientNew = array_search($clientOld, $clientsNewAux);
            if($idClientNew)
            {
                $formAnswerNew["client_new_id"] = $idClientNew;
            }
        }

        foreach ($keyValues as &$keyValue)
        {
            $clientOld = (Object)["form_id" => $keyValue["form_id"], "client_id" => $keyValue["client_id"]];
            $idClientNew = array_search($clientOld, $clientsNewAux);
            if($idClientNew)
            {
                $keyValue["client_new_id"] = $idClientNew;
            }
        }

        $this->command->info("Agregando client_id_new en key values total:".count($keyValues));

        $keyValuesNew = $this->createNewKeyValues($clientsNewAux2);
        $keyValues = array_merge($keyValuesNew, $keyValues);

        $insertQtd = self::$QTD_INSERT_REGISTER;
        $sectionsNewChunk = array_chunk($sectionsNew, $insertQtd);
        $qtd = 0;
        $this->dropRelations();
        $this->createTableNew();
        foreach ($sectionsNewChunk as $sectionNewChunk)
        {

            $this->command->info("guardando $insertQtd sections, $qtd ya insertados, de un total de ".count($sectionsNew));
            DB::table('sections_new')->insert($sectionNewChunk);
            $qtd += $insertQtd;
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

        $clientsNewChunk = array_chunk($clientsNew, $insertQtd);
        $qtd = 0;
        foreach ($clientsNewChunk as $clientNewChunk)
        {
            $this->command->info("guardando $insertQtd clientNew, $qtd ya insertados, de un total de ".count($clientsNew));
            ClientNew::insert($clientNewChunk);
            $qtd += $insertQtd;
        }


        $this->command->info("Renombrando tablas");
        Schema::rename("form_answers", "form_answer_old");
        Schema::rename("form_answer_new", "form_answers");
        Schema::rename("sections", "sections_old");
        Schema::rename("sections_new","sections");

        

        // Schema::table('form_answers', function ($table)
        // {
        //     $table->unsignedInteger('channel_id')->unsigned()->index()->change();
        //     $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
        // });

        // Schema::table('form_answers', function ($table)
        // {
        //     $table->unsignedInteger('form_id')->unsigned()->index()->change();
        //     $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
        // });

        // Schema::table('form_answer_logs', function ($table)
        // {
        //     $table->unsignedInteger('form_answer_id')->unsigned()->index()->change();
        //     $table->foreign('form_answer_id')->references('id')->on('form_answers')->onDelete('cascade');
        // });

        // Schema::table('form_answer_mios_phones', function ($table)
        // {
        //     $table->unsignedInteger('form_answer_id')->unsigned()->index()->change();
        //     $table->foreign('form_answer_id')->references('id')->on('form_answers')->onDelete('cascade');
        // });

        // Schema::table('form_answers_trays', function ($table)
        // {
        //     $table->unsignedInteger('form_answer_id')->unsigned()->index()->change();
        //     $table->foreign('form_answer_id')->references('id')->on('form_answers')->onDelete('cascade');
        // });
    
        // Schema::table('sections', function ($table)
        // {
        //     $table->unsignedInteger('form_id')->unsigned()->index()->change();

        //     $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
        // });

        //alter table `form_answers` add foreign key (`channel_id`) references `channels` (`id`) 
        //alter table `form_answers` add foreign key (`form_id`) references `forms` (`id`) 
        //alter table `form_answer_logs` add foreign key (`form_answer_id`) references `form_answers` (`id`) 
        //alter table `form_answer_mios_phones` add foreign key (`form_answer_id`) references `form_answers` (`id`) 
        //alter table `form_answers_trays` add foreign key (`form_answer_id`) references `form_answers` (`id`) 
        //alter table `sections` add foreign key (`form_id`) references `forms` (`id`) 

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

    private function createNewKeyValues($clientsNewAux)
    {
        $formAnswers = FormAnswer::all();
        $keyValues = [];
        $i = 0;
        foreach ($formAnswers as $formAnswer)
        {
            $this->command->info("Creando KeyValues para formAnswer ".$i++.", ".count($formAnswers));
            $structureAnswer = json_decode($formAnswer->structure_answer);

            foreach ($structureAnswer as $answer)
            {
                if(isset($answer->preloaded) && $answer->preloaded)
                {
                    $clientOld = (Object)["form_id" => $formAnswer->form_id, "client_id" => $formAnswer->client_id];
                    if($clientsNewAux[$formAnswer->form_id.":".$formAnswer->client_id])
                    {
                        $idClientNew = $clientsNewAux[$formAnswer->form_id.":".$formAnswer->client_id]->client_new_id;
                    }
                    $idClientNew = array_search($clientOld, $clientsNewAux) ? array_search($clientOld, $clientsNewAux) : 0;
                    $keyValue = [
                        'form_id' => $formAnswer->form_id,
                        'key' => $answer->key,
                        'value' => $answer->value,
                        'description' => "",
                        'field_id' => $answer->id,
                        'client_id' => $formAnswer->client_id,
                        'client_new_id' => $idClientNew,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    array_push($keyValues ,$keyValue);
                }

            }
        }
        return $keyValues;
    }

    private function dropRelations()
    {
        if(Schema::hasColumn('form_answers', 'channel_id'))
        {
            Schema::table('form_answers', function (Blueprint $table) {
                if($this->foreignKeysExists('form_answers', "form_answers_channel_id_foreign"))
                {
                    $table->dropForeign(['channel_id']);
                }
                $table->unsignedBigInteger('channel_id')->nullable()->change();
            });
        }



        if(Schema::hasColumn('form_answers', 'form_id'))
        {
            Schema::table('form_answers', function (Blueprint $table) {
                if($this->foreignKeysExists('form_answers', "form_answers_form_id_foreign"))
                {
                    $table->dropForeign(['form_id']);
                }
                $table->unsignedBigInteger('form_id')->nullable()->change();
            });
        }



        if(Schema::hasColumn('form_answer_logs', 'form_answer_id'))
        {
            Schema::table('form_answer_logs', function (Blueprint $table) {
                if($this->foreignKeysExists('form_answer_logs', "form_answer_logs_form_answer_id_foreign"))
                {
                    $table->dropForeign(['form_answer_id']);
                }
                $table->unsignedBigInteger('form_answer_id')->nullable()->change();
            });
        }


        if(Schema::hasColumn('form_answer_mios_phones', 'form_answer_id'))
        {
            Schema::table('form_answer_mios_phones', function (Blueprint $table) {
                if($this->foreignKeysExists('form_answer_mios_phones', "form_answer_mios_phones_form_answer_id_foreign"))
                {
                    $table->dropForeign(['form_answer_id']);
                }
                $table->unsignedBigInteger('form_answer_id')->nullable()->change();
            });
        }

        if(Schema::hasColumn('form_answers_trays', 'form_answer_id'))
        {
            Schema::table('form_answers_trays', function (Blueprint $table) {
                if($this->foreignKeysExists('form_answers_trays', "form_answers_trays_form_answer_id_foreign"))
                {
                    $table->dropForeign(['form_answer_id']);
                }
                $table->unsignedBigInteger('form_answer_id')->nullable()->change();
            });
        }

        if(Schema::hasColumn('sections', 'form_id'))
        {
            Schema::table('sections', function (Blueprint $table) {
                if($this->foreignKeysExists('sections', "sections_form_id_foreign"))
                {
                    $table->dropForeign(['form_id']);
                }
                $table->unsignedBigInteger('form_id')->nullable()->change();
            });
        }
    }

    public function foreignKeysExists($table, $foreignKey)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        $foreignKeys = array_map(function($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));

        return in_array($foreignKey, $foreignKeys);
    }

    private function createTableNew()
    {
        Schema::create('sections_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('name_section');
            $table->tinyInteger('type_section');
            $table->json('fields');
            $table->boolean('collapse');
            $table->boolean('duplicate')->default(0);
            $table->tinyInteger('state')->nullable();
            $table->timestamps();
        });

        Schema::create('form_answer_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('client_id');
            $table->json('structure_answer');
            $table->unsignedBigInteger('client_new_id')->default(0);
            $table->json('form_answer_index_data')->nullable();
            $table->string('tipification_time')->nullable();
            $table->unsignedBigInteger('rrhh_id')->default(0);
            $table->timestamps();
        });
    }
}
