<?php

use App\Http\Controllers\ClientNewController;
use Illuminate\Database\Seeder;
use App\Models\ClientNew;
use App\Models\Client;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\Directory;
use App\Models\KeyValue;

class ClientNewSeeder extends Seeder
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
        $clients = Client::all();
        foreach ($clients as $client)
        {
            $formAnswers = FormAnswer::join("forms", "forms.id", "form_answers.form_id")
                ->join("sections", "forms.id", "sections.form_id")
                ->where("form_answers.client_id",$client->id)->where("sections.type_section",1);

            foreach ($formAnswers as $formAnswer)
            {
                $clientNew = ClientNew::find($formAnswer->client_id);
                $clientData = [];
                $clientUnique = [];
                foreach ($formAnswer->fields as $field)
                {
                    if(!$clientNew || ($clientNew && $clientNew->form_id != $formAnswers->form_id))
                    {
                        if($client->$keyDataClient[$field->key])
                        {
                            $clientUnique = ["label" => $field->label,
                            "preloaded" => $field->preloaded,
                            "id" => $field->id,
                            "key" => $field->key,
                            "value" => $client->$keyDataClient[$field->key],
                            "isClientInfo" => true,
                            "client_unique" => true];

                            array_push($clientData, [
                                "id" => $field->id,
                                "value" => $client->$keyDataClient[$field->key],
                            ]);
                        }

                        $createClientNew = new ClientNew([
                            "information_data" => json_encode($clientData),
                            "unique_indentificator" => json_encode($clientUnique),
                            "form_id" => $formAnswer->form_id
                        ]);

                        if(!$clientNew)
                        {
                            $createClientNew->id = $client->id;
                        }
                        $createClientNew->save();

                        Directory::where('form_id', $formAnswer->form_id)
                            ->where('client_id', $formAnswer->client_id)
                            ->update(['client_new_id' => $createClientNew->id]);

                        KeyValue::where('form_id', $formAnswer->form_id)
                            ->where('client_id', $formAnswer->client_id)
                            ->update(['client_new_id' => $createClientNew->id]);

                        FormAnswer::where('form_id', $formAnswer->form_id)
                            ->where('client_id', $formAnswer->client_id)
                            ->update(['client_new_id' => $createClientNew->id]);

                    }

                }
            }
        }
    }
}
