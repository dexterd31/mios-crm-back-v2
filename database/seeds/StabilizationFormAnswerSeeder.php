<?php

use Illuminate\Database\Seeder;
use App\Models\FormAnswer;
use App\Models\Form;
use Database\Seeds\EstabilizacionSeeder;

class StabilizationFormAnswerSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(env('ID_FORM'))
        {
            $forms = Form::where("id", env('ID_FORM'))->get();
        }
        else
        {
            $forms = Form::all();
        }

        $total = 0;
        $a = 0;
        $totalFormularios = count($forms);

        foreach ($forms as &$form)
        {
            $total = $total + count($form->formAnswers);
            $this->command->info("Armando data formAnswer del formulario: ".$form->id." , formAnswer actualizados: .".$a++.", Total de formularios: $totalFormularios");
            $allFilds=[];
            foreach ($form->section as $section)
            {
                $sectionArray = json_decode($section->fields);
                $allFilds = array_merge($allFilds, $sectionArray);
            }
            foreach ($form->formAnswers as &$formAnswer)
            {
                $structureAnswers = json_decode($formAnswer->structure_answer);
                $formAnswerIndexData = [];
                foreach ($structureAnswers as &$structureAnswer)
                {
                    foreach ($allFilds as $fild)
                    {
                        
                        if(isset($fild->idsOld) && in_array($structureAnswer->id, $fild->idsOld))
                        {
                            $structureAnswer->id = $fild->id;
                            $structureAnswer->key = $fild->key;
                            $structureAnswer->preloaded = $fild->preloaded;
                            $structureAnswer->label = $fild->label;
                            $structureAnswer->isClientInfo = $fild->isClientInfo;
                            $structureAnswer->client_unique = $fild->client_unique;
                            if(isset($fild->options))
                            {
                                foreach ($fild->options as $option)
                                {
                                    if($option->idOld == $structureAnswer->value)
                                    {
                                        $structureAnswer->value = $option->id;
                                    }
                                }
                            }
                        }
                        else if($structureAnswer->id == $fild->id)
                        {
                            $structureAnswer->preloaded = $fild->preloaded;
                            $structureAnswer->label = $fild->label;
                            $structureAnswer->isClientInfo = $fild->isClientInfo;
                            $structureAnswer->client_unique = $fild->client_unique;
                        }
                    }
                    array_push($formAnswerIndexData, (Object)["id"=>$structureAnswer->id, "value"=>$structureAnswer->value]);                    
                }
                $formAnswer->form_answer_index_data = json_encode($formAnswerIndexData);
                $formAnswer->structure_answer = json_encode($structureAnswers);
            }
        }

        $i = 1;
        foreach ($forms as $form)
        {
            foreach ($form->formAnswers as $formAnswer)
            {
                $formAnswer->save();
                $this->command->info("Actualizando formAnswer del formulario: ".$form->id." , formAnswer actualizados: .".$i++.", Total: $total");
            }
        }
    }
}
