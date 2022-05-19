<?php

namespace App\Traits;

use App\Models\FormAnswer;
use App\Models\Section;
use stdClass;

trait CheckDuplicateSections
{
    public function checkDuplicateSections($formAnswerId)
    {
        $answer=json_decode(FormAnswer::where('id',$formAnswerId)->first()->structure_answer);
        $seccionesDuplicar=[];
        $indicesDuplicar=[];
        foreach($answer as $fieldAnswer){
            if(isset($fieldAnswer->duplicated->Section)){
                $idSeccion=$fieldAnswer->duplicated->Section->id;
                $nameSeccion=$fieldAnswer->duplicated->Section->name;
                $hashSeccion=base64_encode($idSeccion.$nameSeccion);
                if(in_array($hashSeccion,$indicesDuplicar)){
                    $indice=array_search($hashSeccion,$indicesDuplicar);
                }else{
                    array_push($indicesDuplicar,$hashSeccion);
                    $indice=array_search($hashSeccion,$indicesDuplicar);
                }
                if(isset($seccionesDuplicar[$indice])){
                    $seccionesDuplicar[$indice]=$this->changeFieldSection($fieldAnswer,$seccionesDuplicar[$indice]);
                }else{
                    $seccionesDuplicar[$indice]=$this->createdDuplicatedSections($idSeccion,$nameSeccion);
                    $seccionesDuplicar[$indice]=$this->changeFieldSection($fieldAnswer,$seccionesDuplicar[$indice]);
                }
            }
        }

        return $seccionesDuplicar;
    }

    private function createdDuplicatedSections($idSection,$nameDuplicatedSection){
        $formsSections=Section::select('name_section','type_section','fields','collapse')->where('id',$idSection)->first();
        $duplicatedSection=new stdClass();
        $duplicatedSection->id=time();
        $duplicatedSection->name_section=$nameDuplicatedSection;
        $duplicatedSection->collapse=$formsSections->collapse;
        $duplicatedSection->duplicate=0;
        $duplicatedSection->see=true;
        $duplicatedSection->fields=json_decode($formsSections->fields);
        return $duplicatedSection;
    }

    private function changeFieldSection($duplicatefield,$duplicatedSection){
        foreach($duplicatedSection->fields as $originalField){
            if($originalField->id==$duplicatefield->duplicated->idOriginal){
                $originalField->id=$duplicatefield->id;
                $originalField->key=$duplicatefield->key;
                $originalField->label=$duplicatefield->label;
                $originalField->duplicated=$duplicatefield->duplicated;
            }
        }
        return $duplicatedSection;
    }
}
