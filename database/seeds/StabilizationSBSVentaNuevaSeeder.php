<?php

use App\Models\Client;
use App\Models\ClientNew;
use Illuminate\Database\Seeder;
use App\Models\Form;

class StabilizationSBSVentaNuevaSeeder extends Seeder
{
    private $sectionPhone;
    private $clientData = [];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $form = Form::find(13);

        foreach ($form->section as &$section)
        {
            $fields = json_decode($section->fields);
            foreach ($fields as &$field)
            {
                if($field->key == 'document')
                {
                    $field->client_unique = false;
                }
                else if($field->key == 'phone')
                {
                    $this->sectionPhone = $field;
                    $field->client_unique = true;
                }
            }
            $section->fields = json_encode($fields);
            $section->save();
        }

        foreach ($form->formAnswers as $formAnswer)
        {
            $clientNew = ClientNew::find($formAnswer->client_new_id);
            $structureAnswers = json_decode($formAnswer->structure_answer);
            foreach ($structureAnswers as &$answers)
            {
                if($answers->key == 'phone')
                {
                    $answers->client_unique = true;
                    if($clientNew)
                    {
                        $clientNew->unique_indentificator = json_encode($answers);
                        $clientNew->save();
                    }
                }
                else if($answers->key == 'document')
                {
                    $answers->client_unique = false;
                }
            }
            $formAnswer->structure_answer = json_encode($structureAnswers);
            $formAnswer->save();
        }

        foreach ($form->directory as $directory)
        {
            if($directory->client_new_id != 0)
            {
                $this->updateClientNew($directory);
            }
            else
            {
                $clientNew = ClientNew::where("form_id", $directory->form_id)->where("cliet_old_id", $directory->client_id)->first();
                if(!$clientNew)
                {
                    $this->createClientNew($directory);
                }
            }
        }
    }

    private function updateClientNew($directory)
    {
        $clientNew = ClientNew::find($directory->client_new_id);
        $directorysData = json_decode($directory->data);
        foreach ($directorysData as $directoryData)
        {
            if($directoryData->id == $this->sectionPhone->id)
            {
                $clientNew->unique_indentificator = json_encode([(Object)[
                    "label" => $this->sectionPhone->label,
                    "preloaded" => true,
                    "id" => $this->sectionPhone->id,
                    "key" => $this->sectionPhone->key,
                    "value" => $directoryData->value,
                    "isClientInfo" => true,
                    "client_unique" => true,
                    "cliet_old_id" => $directory->client_id
                ]]);
                $clientNew->save();
                return;
            }
        }
    }

    private function createClientNew($directory)
    {
        $directorysData = json_decode($directory->data);
        $clientNewData = [];
        foreach ($directorysData as $directoryData)
        {
            if($directoryData->id == $this->sectionPhone->id)
            {
                $uniqueIndentificator = json_encode([(Object)[
                    "label" => $this->sectionPhone->label,
                    "preloaded" => true,
                    "id" => $this->sectionPhone->id,
                    "key" => $this->sectionPhone->key,
                    "value" => $directoryData->value,
                    "isClientInfo" => true,
                    "client_unique" => true,
                    "cliet_old_id" => $directory->client_id
                ]]);
            }
            if(array_key_exists($directoryData->id, $this->clientData))
            {
                array_push($clientNewData, (Object)[
                    "id" => $this->clientData[$directoryData->id]->id,
                    "key" => $this->clientData[$directoryData->id]->key
                ]);
            }
        }
        $clientNew = new ClientNew([
                "form_id" => $directory->form_id,
                "information_data" => json_encode($clientNewData),
                "unique_indentificator" => $uniqueIndentificator,
                "cliet_old_id" => $directory->client_id
        ]);

        $clientNew->save();

    }
}
