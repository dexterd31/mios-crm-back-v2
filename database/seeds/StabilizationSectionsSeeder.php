<?php

use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\Section;

class StabilizationSections2Seeder extends Seeder
{
    private $keyDataClient = array(
        "firstName" => "first_name",
        "middleName" => "middle_name",
        "lastName" => "first_lastname",
        "secondLastName" => "second_lastname",
        "document_type_id" => "document_type_id",
        "document" => "document",
        "phone" => "phone",
        "email" => "email"
    );
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $newFildsForms = [];
        if(env('ID_FORM'))
        {
            $forms = Form::where("id", env('ID_FORM'))->get();
        }else
        {
            $forms = Form::all();
        }

        $forms = $this->updateDependencies($forms);
        $forms = $this->updateFilds($forms);
        $this->saveFromsAdnSections($forms);
    }

    private function getActivatorsInFather($name, $optionsFather)
    {
        foreach($optionsFather as $optionFather)
        {
            if($optionFather->name == $name)
            {
                return [(Object)[
                    "id"=> $optionFather->id ,
                    "name"=> $optionFather->name,
                ]];
            }
        }
    }
    private function mregeSections($sections)
    {
        $allFilds = [];
        foreach ($sections as $section)
        {
            $sectionArray = json_decode($section->fields);
            $allFilds = array_merge($allFilds, $sectionArray);
        }
        return $allFilds;
    }


    private function updateFilds(&$forms)
    {
        foreach ($forms as &$form)
        {
            foreach ($form->section as &$section)
            {
                $fields = json_decode($section->fields);
                foreach ($fields as &$field)
                {
                    $field->isClientInfo = false;
                    $field->client_unique = false;
        
                    if(!isset($field->isSon))
                    {
                        $field->isSon = false;
                    }

                    if(array_key_exists($field->key, $this->keyDataClient))
                    {
                        $field->isClientInfo = true;
                        if($field->key == "document")
                        {
                            $field->preloaded = true;
                            $field->client_unique=true;
                            $form->fields_client_unique_identificator = json_encode([$field]);
                        }
                    }
                }
                $section->fields = json_encode($fields);
            }
        }
        return $forms;
    }

    private function updateDependencies($forms)
    {
        foreach ($forms as &$form)
        {
            $allFilds = $this->mregeSections($form->section);
            \Log::info($allFilds);
            foreach ($form->section as &$section)
            {
                $fields = json_decode($section->fields);
                foreach ($fields as &$field)
                {
                    foreach ($allFilds as $fildAux)
                    {
                        //Verifica si el campo tiene dependencia
                        \Log::info("entroooooooooooooooooo");
                        if(isset($field->dependencies) && isset($field->dependencies[0]) && isset($field->dependencies[0]->idField))
                        {
                            \Log::info("entroooooooooooooooooo");
                            //busca el padre
                            if($field->dependencies[0]->idField == $fildAux->id)
                            {
                                $activators = $this->getActivatorsInFather($field->dependencies[0]->name, $fildAux->options);
                                $field->isSon = true;
                               
                                $field->dependencies = [
                                    (Object)[
                                        "label" => $fildAux->label,
                                        "idField" => $fildAux->id,
                                        "options" => $field->options,
                                        "activators" => $activators,
                                    ]
                                ];
                            }
                        }
                    }
                }
                $section->fields = json_encode($fields);
            }
        }
        return $forms;
    }

    private function saveFromsAdnSections($forms)
    {
        foreach ($forms as $form)
        {
            foreach ($form->section as $section)
            {
                $section->save();
            }
            $form->save();
        }
    }
}
