<?php

use App\Models\ClientNew;
use Illuminate\Database\Seeder;
use App\Models\Form;
use App\Models\FormAnswer;

class StabilizationClientNewSeeder extends Seeder
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
            $formAnswer = FormAnswer::where("form_id", env('ID_FORM'))->orderByDesc('id')->get();
        }
        else
        {
            $formAnswer = FormAnswer::all();
        }
        $clientsNew = [];
        $clientNewList = [];
        $a = 0;
        $clientNewId = ClientNew::max('id') ? ClientNew::max('id') + 1 : 1;

        
        \Log::info("ulyimo Id: === ". $clientNewId);
        $total = count($formAnswer);
        foreach ($formAnswer as &$answer)
        {
            $this->command->info("Armando data clientNew del formulario: ".$answer->form_id." , clientNew armadors: .".$a++.", Total de formAnswers: $total");
            //Verifica si ja fue creado um cliente new
            if(in_array((Object)[$answer->form_id, $answer->client_id], $clientNewList))
            {
                $clientNewIdAux = array_search((Object)[$answer->form_id, $answer->client_id], $clientNewList);
                $answer->client_new_id = $clientNewIdAux;
                continue;
            }
            $structureAnswers = json_decode($answer->structure_answer);
            $clientData = [];
            foreach ($structureAnswers as $structureAnswer)
            {
                array_push($clientData, [
                    "id" => $structureAnswer->id,
                    "value" => $structureAnswer->value,
                ]);

                if(isset($structureAnswer->isClientInfo) && $structureAnswer->key == 'document')
                {
                    $clientUnique = [(Object)[
                        "label" => isset($structureAnswer->label) ? $structureAnswer->label : "no se encuntra label en el formAnswer" ,
                        "preloaded" => true,
                        "id" => $structureAnswer->id,
                        "key" => $structureAnswer->key,
                        "value" => $structureAnswer->value,
                        "isClientInfo" => true,
                        "client_unique" => true,
                        "cliet_old_id" => $answer->client_id
                    ]];
                }
            }
            if(isset($clientUnique))
            {
                $clientNewId++;
                array_push($clientsNew,[
                    "id" => $clientNewId,
                    "information_data" => json_encode($clientData),
                    "unique_indentificator" => json_encode($clientUnique),
                    "form_id" => $answer->form_id,
                    "cliet_old_id" => $answer->client_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
    
                $clientNewList[$clientNewId] = (Object)[$answer->form_id, $answer->client_id];
                $answer->client_new_id = $clientNewId;
            }

        }
        $i = 0;
        foreach ($formAnswer as $answer)
        {
            $this->command->info('Update formAnswer '.$i++.' Total: '.count($formAnswer));
            $formAnswerUpdate = FormAnswer::find($answer->id);
            $formAnswerUpdate->client_new_id = $answer->client_new_id;
            $formAnswerUpdate->save();
        }

        $this->command->info('Insertadno '.count($clientsNew).'  clientNew');
        $clientsNewChunk = array_chunk($clientsNew, 100);
        $qtd = 0;
        foreach ($clientsNewChunk as $clientNewChunk)
        {
            $this->command->info("guardando 100 ClientNew, $qtd ya insertados, de un total de ".count($clientsNew));
            ClientNew::insert($clientNewChunk);
            $qtd += 100;
        }
    }
}
