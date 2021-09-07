<?php

use Illuminate\Database\Seeder;
use App\Models\ClientNew;
use App\Models\Client;
use App\Models\FormAnswer;

class ClientNewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $keyDataClient = array("firstName","middleName","lastName","secondLastName", "document_type_id", "document", "phone", "email");
        ///id,structure_answer,created_at,updated_at,form_id,channel_id,rrhh_id,client_new_id,form_answer_index_data
        $formAnswers = FormAnswer::all();
        foreach ($formAnswers as $formAnswer)
        {
            $structureAnswer = json_decode($formAnswer->structure_answer);
            $informationData = [];
            $uniqueIndentificator = null;
            foreach ($structureAnswer as $answer)
            {
                $answer->isClientInfo = false;
                if(in_array($answer->key, $keyDataClient))
                {
                    array_push($informationData, array(
                        "id"=>$answer->id,
                        "value"=>$answer->value,
                    ));
                    $answer->isClientInfo = true;
                }
                $answer->client_unique = false;
                if ($answer->key == "document")
                {
                    $answer->client_unique = true;
                    $uniqueIndentificator = $answer;
                }
                $clientNew = new ClientNew (array(
                    "id" => $answer->client_id,
                    "form_id" => $formAnswer->form_id,
                    "information_data" => json_encode($informationData),
                    "unique_indentificator" => json_encode($uniqueIndentificator),
                ));
                $clientNew->save();
            }
            $formAnswers->save();
        }
    }
}
