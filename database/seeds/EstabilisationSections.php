<?php

use Illuminate\Database\Seeder;
use app\Models\Section;
use App\Models\Form;

class EstabilisationSections extends Seeder
{
    private $lestId;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $forms = Form::where("id", 21)->get();
        //$forms = Form::all();
        $this->lestId = time();
        foreach ($forms as $form)
        {
            $newFilds = $this->creandoArboldeDependencias($form->section);
            //\Log::info(json_encode($newFilds, JSON_PRETTY_PRINT));
            $this->updateFilds($newFilds, $form->section);
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
        //\Log::info(json_encode($arbolDeDependencias, JSON_PRETTY_PRINT));
        $newFilds= [];
        $fildsSinDependencias = [];
        $this->updateSections($arbolDeDependencias, $newFilds, $fildsSinDependencias);
        $newFilds = array_merge($fildsSinDependencias, $newFilds);
        \Log::info(json_encode($newFilds, JSON_PRETTY_PRINT));
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
        $lestOptionId= 1;
        $fieldNews = [];
        foreach ($hijos as $hijo)
        {
            $fieldNewKey = null;
            foreach ($fieldNews as $key => $field)
            {
                if($field->type == $hijo->type &&
                    $field->controlType == $hijo->controlType &&
                    $field->idSection == $hijo->idSection)
                {
                    $fieldNewKey = $key;
                    break;
                }
            }
            if(!$fieldNewKey)
            {
                array_push($fieldNews, (Object)[
                    "id" => $this->lestId++,
                    "type" => $hijo->type,
                    "key" => $hijo->key,
                    "controlType" => $hijo->controlType,
                    "label" => $hijo->label,
                    "value" => "",
                    "required" => $hijo->required,
                    "canAdd"=> $hijo->canAdd,
                    "minLength"=> $hijo->minLength,
                    "maxLength"=> $hijo->maxLength,
                    "inReport"=> $hijo->inReport,
                    "disabled"=> $hijo->disabled,
                    "cols"=> $hijo->cols,
                    "preloaded"=> $hijo->preloaded,
                    "isSon"=> true,
                    "dependencies"=> [],
                    "editRoles" => $hijo->editRoles,
                    "seeRoles" => $hijo->seeRoles,
                    "tooltip" => $hijo->tooltip,
                    "options" => [],
                    "idsOld" => [],
                    "idSection" => $hijo->idSection
                ]);

                $fieldNewKey = array_key_last($fieldNews);
            }
            array_push($fieldNews[$fieldNewKey]->idsOld, $hijo->id);
            foreach ($hijo->options as &$option)
            {
                $id = isset($option->id) ? $option->id: $option->Id;
                $option->idOld = $id;
                $option->id = $lestOptionId++;

                array_push($fieldNews[$fieldNewKey]->options, $option); 
            }
            foreach($padre->options as $optionPadre)
            {
                if($optionPadre->name == $hijo->dependencies[0]->name)
                {
                    $activators = [(Object)[
                        "id"=> $option->id,
                        "name"=> $optionPadre->name,
                        "idOld"=> $option->idOld,
                    ]];
                }
            }
            array_push($fieldNews[$fieldNewKey]->dependencies, (Object)[
                    "label" => $padre->label,
                    "idField" => $padre->id,
                    "options" => $hijo->options,
                    "activators" => $activators,
            ]);
        }
        return $fieldNews;
    }

    private function updateFilds($newFilds, $sections)
    {
        $filds = [];
        foreach ($sections as $section)
        {
            foreach ($newFilds as $newFild)
            {
                if($newFild->idSection == $section->id)
                {
                    array_push($filds, $newFild);
                }
            }
            $section->fields = json_encode($filds);
            $section->save();
        }
    }
}
