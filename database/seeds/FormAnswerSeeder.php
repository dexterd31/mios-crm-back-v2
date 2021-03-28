<?php

use Illuminate\Database\Seeder;
use App\Models\FormAnswer;
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
                'user_id' => 1,
                'client_id'=> 1,
                'form_id' => 2,
                'channel_id' => 1,
                'structure_answer'=> array(
                    [
                        "firstName"=> "Karol",
                        "middleName"=>"Andrea", 
                        "lastName"=> "García", 
                        "secondLastName"=>"Bohorquez",
                        "document"=>"1212123",
                        "phone"=>"1234567890",
                        "email"=>"karol@gmail.com"
                    ],
                    ["placa"=> "12345676767",
                     "marca"=> "renault"
                    ])
            ]);
            foreach ($form_answer as $formanswer)
            {
                $Form = new FormAnswer();
                $Form->user_id = $formanswer['user_id'];
                $Form->client_id = $formanswer['client_id'];
                $Form->form_id = $formanswer['form_id'];
                $Form->channel_id = $formanswer['channel_id'];
                $Form->structure_answer = json_encode($formanswer['structure_answer']);
                $Form->save();
            }
    }
}
