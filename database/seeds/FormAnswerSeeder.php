<?php

use Illuminate\Database\Seeder;
use App\Models\FormAnswer;
use App\Models\Client;
class FormAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $form_answer = array(
            [
                'rrhh_id' => 1,
                'form_id' => 2,
                'channel_id' => 1,
                'structure_answer'=> array(
                        [
                            "id" => 1616799311180,
                            "key" => "firstName",
                            "value" => "Jair"
                        ],
                        [
                            "id" => 1616799311181,
                            "key" => "middleName",
                            "value" => "Armando"
                        ],
                        [
                        "id" => 1616799311182,
                        "key" => "lastName",
                        "value" => "Celis"
                        ],
                        [
                        "id" => 1616799311183,
                        "key" => "secondLastName",
                        "value" => "Torrado"
                        ],
                        [
                        "id" => 161679930000,
                        "key" => "document_type_id",
                        "value" => 1
                        ],
                        [
                        "id" => 1616799311184,
                        "key" => "document",
                        "value" => "1032399970"
                        ],
                        [
                        "id" => 1616799311185,
                        "key" => "phone",
                        "value" => "3207671490"
                        ],
                        [
                        "id" => 1616799311186,
                        "key" => "email",
                        "value" => "jair.celis.torrado@gmail.om"
                        ],
                        [
                        "id" => 1616799311187,
                        "key" => "placa",
                        "value" => "brs123"
                        ],
                        [
                        "id" => 1616799311188,
                        "key" => "marca",
                        "value" => "Mazda"
                    ])
                        ]);
            foreach ($form_answer as $formanswer)
            {
        
                $Form = new FormAnswer();
                $Form->rrhh_id = $formanswer['rrhh_id'];
                $Form->form_id = $formanswer['form_id'];
                $Form->channel_id = $formanswer['channel_id'];
                $Form->structure_answer = json_encode($formanswer['structure_answer']);
                $Form->save();


            }
    }
}
