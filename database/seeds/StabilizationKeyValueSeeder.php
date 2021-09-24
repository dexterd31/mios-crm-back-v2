<?php

use Illuminate\Database\Seeder;
use App\Models\FormAnswer;
use App\Models\KeyValue;

class StabilizationKeyValueSeeder extends Seeder
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
            $formAnswer = FormAnswer::where("form_id", env('ID_FORM'))->get();
        }
        else
        {
            $formAnswer = FormAnswer::all();
        }
        $keyValues = [];
        $a = 0;
        $total = count($formAnswer);
        foreach ($formAnswer as $answer)
        {
            $this->command->info("Armando data KeyValue del formulario: ".$answer->form_id." , KeyValue armadors: .".$a++.", Total de formAnswers: $total");
            $structureAnswers = json_decode($answer->structure_answer);
            foreach ($structureAnswers as $structureAnswer)
            {
        
                if($structureAnswer->preloaded)
                {
                    $keyValue = [
                        'form_id' => $answer->form_id,
                        'key' => $structureAnswer->key,
                        'value' => $structureAnswer->value,
                        'description' => "",
                        'field_id' => $structureAnswer->id,
                        'client_new_id' => $answer->client_new_id,
                        'client_id' => $answer->client_id,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    array_push($keyValues, $keyValue);
                }
            }
        }

        $insertQtd = 100;
        $keyValuesChunk = array_chunk($keyValues, $insertQtd);
        $qtd = 0;
        foreach ($keyValuesChunk as $keyValueChunk)
        {
            $this->command->info("guardando $insertQtd KeyValue, $qtd ya insertados, de un total de ".count($keyValues));
            KeyValue::insert($keyValueChunk);
            $qtd += $insertQtd;
        }
    }
}
