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
            //busca respuesta para cada cliente
            $formAnswers = FormAnswer::join("forms", "forms.id", "form_answers.form_id")
                ->join("sections", "forms.id", "sections.form_id")
                ->where("form_answers.client_id",$client->id)->where("sections.type_section",1)->select("form_answers.*", "sections.id as sections_id", "sections.fields as fields")->get();

            //idForm es un array con las lista de formularios para cual ya se creo el cliente
            $idForms = [];
            //crea un cliente para cada formulario que tenga tipificacion
            foreach ($formAnswers as $formAnswer)
            {
                \Log::info($formAnswer->type_section);
                $clientData = [];
                $clientUnique = [];
                $fields = json_decode($formAnswer->fields );

                if(!in_array($formAnswer->form_id, $idForms))
                {
                    array_push($idForms, $formAnswer->form_id);
                    foreach ($fields as $field)
                    {
                        $key = array_key_exists($field->key,$keyDataClient) ? $keyDataClient[$field->key] : null;
                        if($key && $client->$key)
                        {
                            if($field->key == "document")
                            {
                                $clientUnique = [
                                    "label" => $field->label,
                                    "preloaded" => $field->preloaded,
                                    "id" => $field->id,
                                    "key" => $field->key,
                                    "value" => $client->$key,
                                    "isClientInfo" => true,
                                    "client_unique" => true
                                ];
                            }

                            array_push($clientData, [
                                "id" => $field->id,
                                "value" => $client->$key,
                            ]);
                        }
                    }

                    $createClientNew = new ClientNew([
                        "information_data" => json_encode($clientData),
                        "unique_indentificator" => json_encode($clientUnique),
                        "form_id" => $formAnswer->form_id
                    ]);
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
