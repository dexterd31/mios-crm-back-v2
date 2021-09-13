<?php

use Illuminate\Database\Seeder;
use App\Models\FormAnswer;

class UpdateFormAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
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

        $formAnswers = FormAnswer::all();
        foreach($formAnswers as $formAnswer)
        {
            $structureAnswers = json_decode($formAnswer->structure_answer);
            $formAnswerIndexData = [];
            foreach ($structureAnswers as $structureAnswer)
            {
                if(array_key_exists($structureAnswer->key, $keyDataClient))
                {
                    $structureAnswer->isClientInfo = true;
                    if($structureAnswer->key == "document")
                    {
                        $structureAnswer->preloaded = true;
                        $structureAnswer->client_unique = true;
                    }
                    array_push($formAnswerIndexData, [
                        "id"=> $structureAnswer->id,
                        "value"=> $structureAnswer->value
                    ]);
                }                
            }
            $formAnswer->structure_answer = json_encode($structureAnswers);
            $formAnswer->form_answer_index_data = $formAnswerIndexData;
            $formAnswer->save();
        }

    }
}
