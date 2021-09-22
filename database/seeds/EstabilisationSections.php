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
        $forms = Form::where("id", 3)->get();
        $forms = Form::all();
        $this->lestId = time();
        foreach ($forms as $form)
        {
            $arbolDeDependencias = $this->creandoArboldeDependencias($form->section);
        }
    }

    //Metodo para creat unm arbol con las dependencias
    private function creandoArboldeDependencias($sections)
    {
        $arbolDeDependencias = (Object)[
            "noAux" => true,
            "hijos" => []
        ];
        foreach ($sections as $section)
        {
            $fields = json_decode($section->fields);
            foreach ($fields as $field)
            {
                $this->agregarElementoEnArbol($arbolDeDependencias, $field);
                if(!isset($field->campoInsertado))
                {
                    array_push($arbolDeDependencias->hijos, $field);
                }
            }
        }
        //\Log::info(json_encode($arbolDeDependencias, JSON_PRETTY_PRINT));
        $newFilds = $this->updateSections($arbolDeDependencias);
        \Log::info(json_encode($newFilds, JSON_PRETTY_PRINT));
        return $arbolDeDependencias;
    }

    private function agregarElementoEnArbol(&$arbol, &$field)
    {
        if(!isset($arbol->noAux))
        {
            if($field->id == $arbol->id)
            {
                return;
            }
            //verifica si el campo es hijo
            if($this->checaSiYoSoyPadre($field->dependencies, $arbol->id))
            {
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

    private function mesclaFilds($nodo, &$newFilds)
    {
        if(!isset($nodo->hijos))
        {
            return;
        }
        if(!isset($nodo->noAux))
        {
            array_push($newFilds, $this->createNewFilds($nodo->hijos, $nodo->id, $nodo->label));
        }
        foreach ($nodo->hijos as $hijo)
        {
            $this->mesclaFilds($hijo, $newFilds);
        }

    }

    private function createNewFilds($hijos, $idPadre, $labelPadre)
    {
        $lestOptionId= 1;
        $fieldNew = (Object)[
            "id" => $this->lestId++,
            "type" => $hijos[0]->type,
            "key" => $hijos[0]->key,
            "controlType" => $hijos[0]->controlType,
            "label" => $hijos[0]->label,
            "value" => "",
            "required" => $hijos[0]->required,
            "canAdd"=> $hijos[0]->canAdd,
            "minLength"=> $hijos[0]->minLength,
            "maxLength"=> $hijos[0]->maxLength,
            "inReport"=> $hijos[0]->inReport,
            "disabled"=> $hijos[0]->disabled,
            "cols"=> $hijos[0]->cols,
            "preloaded"=> $hijos[0]->preloaded,
            "isSon"=> true,
            "dependencies"=> [],
            "editRoles" => $hijos[0]->editRoles,
            "seeRoles" => $hijos[0]->seeRoles,
            "tooltip" => $hijos[0]->tooltip,
            "options" => []
        ];

        foreach ($hijos as $hijo)
        {
            $hijo->id = $this->lestId;
            $activatorsNew = null;
            foreach ($hijo->options as &$option)
            {
                $option->idOld = isset($option->id)? $option->id: $option->Id;
                $option->id = $lestOptionId++;
                $option->name = isset($option->Name)? $option->Name: $option->name;
                if(isset($hijo->dependencies[0]->name) && $hijo->dependencies[0]->name == $option->name)
                {
                    $activatorsNew = [];
                    $activatorsNew[0] = (Object)[
                        "name"=>$option->name,
                        "idOld"=>$option->idOld,
                        "id"=>$option->id
                    ];
                }
            }
            if(count($hijo->dependencies) > 0)
            {
                array_push($fieldNew->dependencies ,(Object)[
                    "activators" => $activatorsNew,
                    "idField" => $idPadre,
                    "label" => $labelPadre,
                    "options" => $hijo->options
                ]);
            }

        }
        return $fieldNew;
    }

    public function updateSections($arbolDeDependencias)
    {
        $newFilds = [];
        $this->mesclaFilds($arbolDeDependencias, $newFilds);
        foreach ($arbolDeDependencias->hijos as $filsd)
        {
            unset($filsd->hijos);
            array_push($newFilds, $filsd);
        }
        return $newFilds;
    }
}
