<?php

use Illuminate\Database\Seeder;
use app\Models\Section;
use App\Models\Form;

class StabilizationSectionsSeeder extends Seeder
{
    private $lestId = 1;
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

        $this->lestId = time();
        foreach ($forms as &$form)
        {
            $newFilds = $this->creandoArboldeDependencias($form->section);
            $newFilds = $this->updateIdFilds($newFilds, $form);
            $newFildsForms[$form->id] = $newFilds;
        }

        $total = count($forms);
        $i = 1;
        foreach ($forms as $form)
        {
            $this->saveFilds($newFildsForms[$form->id], $form->section);
            //$form->save();
            $this->command->info("Actualizando sections del formulario: ".$form->id." , Formularios actualizados: .".$i++.", Total: $total");
        }
    }

    //Metodo para creat unm arbol con las dependencias
    private function creandoArboldeDependencias($sections)
    {
        $arbolDeDependencias = (Object)[
            "nodoHead" => true,
            "hijos" => []
        ];
        foreach ($sections as $section)
        {
            $fields = json_decode($section->fields);
            foreach ($fields as $field)
            {
                $field->idSection = $section->id;
                $field->isSon = false;
                $this->agregarElementoEnArbol($arbolDeDependencias, $field);
                if(!isset($field->campoInsertado))
                {
                    array_push($arbolDeDependencias->hijos, $field);
                }
            }
        }
        $newFilds= [];
        $fildsSinDependencias = [];
        $this->updateSections($arbolDeDependencias, $newFilds, $fildsSinDependencias);
        $newFilds = array_merge($fildsSinDependencias, $newFilds);
        return $newFilds;
    }

    private function agregarElementoEnArbol(&$arbol, &$field)
    {
        if(!isset($arbol->nodoHead))
        {
            if($field->id == $arbol->id)
            {
                return;
            }
            //verifica si el campo es hijo
            if($this->checaSiYoSoyPadre($field->dependencies, $arbol->id))
            {
                $field->isSon = true;
                $field->campoInsertado = true;
                if(!isset($arbol->hijos))
                {
                    $arbol->hijos = array();
                }
                array_push($arbol->hijos, $field);
            }
            //verifica si en campo es padre
            else if($this->checaSiYoSoyPadre($arbol->dependencies, $field->id))
            {
                $field->campoInsertado = true;
                if(!isset($field->hijos))
                {
                    $field->hijos = array();
                }
                array_push($field->hijos, $arbol);
            }
        }

        if(isset($arbol->hijos))
        {
            foreach ($arbol->hijos as $hijo)
            {
                $this->agregarElementoEnArbol($hijo, $field);
            }
        }
    }
    private function checaSiYoSoyPadre($dependencies, $id)
    {

        foreach ($dependencies as $dependend)
        {
            if($dependend->idField == $id)
            {
                return true;
            }
        }
        return false;
    }

    private function updateSections(&$arbol, &$newSectons, &$sectionsSinDependencias)
    {
        if(!isset($arbol->nodoHead))
        {
            if(isset($arbol->hijos))
            {
                $newSectonHijos = $this->createNewSection($arbol, $arbol->hijos);
                $newSectons = array_merge($newSectons, $newSectonHijos);
            }
        }

        if(isset($arbol->hijos))
        {
            foreach ($arbol->hijos as $hijo)
            {
                $this->updateSections($hijo, $newSectons, $sectionsSinDependencias);
            }
        }
        if(isset($arbol->nodoHead))
        {
            foreach ($arbol->hijos as $hijo)
            {
                unset($hijo->hijos);
                array_push($sectionsSinDependencias, $hijo);
            }
        }

    }

    private function createNewSection($padre, $hijos)
    {
        $fieldNews = [];
        foreach ($hijos as $hijo)
        {
            $hijo->isSon = true;
            foreach($padre->options as $optionPadre)
            {
                if($optionPadre->name == $hijo->dependencies[0]->name)
                {
                    $activators = [(Object)[
                        "id"=> $optionPadre->id ,
                        "name"=> $optionPadre->name,
                    ]];
                }
            }
            $hijo->dependencies = [
                (Object)[
                    "label" => $padre->label,
                    "idField" => $padre->id,
                    "options" => $hijo->options,
                    "activators" => $activators,
                ]
            ];

            array_push($fieldNews, $hijo);
        }
        return $fieldNews;
    }

    private function saveFilds($newFilds, $sections)
    {
        foreach ($sections as $section)
        {
            $filds = [];
            foreach ($newFilds as $newFild)
            {
                if($newFild->idSection == $section->id)
                {
                    array_push($filds, $newFild);
                }
            }
            $section->fields = json_encode($filds);
            //$section->save();
        }
    }

    private function updateIdFilds($filds, &$form)
    {
        foreach ($filds as &$fild)
        {
            $fild->isClientInfo = false;
            $fild->client_unique = false;

            if(array_key_exists($fild->key, $this->keyDataClient))
            {
                $fild->isClientInfo = true;
                if($fild->key == "document")
                {
                    $fild->preloaded = true;
                    $fild->client_unique=true;
                    $form->fields_client_unique_identificator = json_encode([$fild]);
                }
            }
            if(isset($fild->hijos))
            {
                unset($fild->hijos);
            }
          
        }
        return $filds;
    }
}
