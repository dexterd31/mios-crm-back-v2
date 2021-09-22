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
        $this->updateSections($arbolDeDependencias, $newFilds);
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

    private function updateSections(&$arbol, &$newSection)
    {
        if(!isset($arbol->nodoHead) && isset($arbol->hijos))
        {
            $this->updateDependecias($arbol, $arbol->hijos);
        }
        if(isset($arbol->hijos))
        {
            foreach ($arbol->hijos as $hijo)
            {
                $this->updateSections($hijo, $newSection);
            }
        }
        if(!isset($arbol->nodoHead))
        {
            unset($arbol->hijos);
            array_push($newSection, $arbol);
        }

    }

    private function updateDependecias($padre, $hijos)
    {
        foreach ($hijos as $hijo)
        {
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
        }
    }

    private function updateFilds($newFilds, $sections)
    {
        $filds = [];
        foreach ($sections as $section)
        {

            foreach ($newFilds as $newFild)
            {
                \Log::info(json_encode($newFild, JSON_PRETTY_PRINT));
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
