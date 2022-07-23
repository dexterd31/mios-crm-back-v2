<?php

namespace App\Managers;

use App\Models\ClientNew;

class ClientsManager
{
    public function findClient(array $data)
    {
        
        $clientNew = new ClientNew;
        
        if (isset($data['client_new_id'])){
            $clientNew = $clientNew->find($data['client_new_id']);

        } else {
            $clientNew = $clientNew::where("form_id", $data['form_id'])->get()
            ->filter(function ($client) use ($data) {
                $found = false;
                $isMatchUniqueIdentificator = false;
                $isMatchInformationData = false;
                $uniqueIdentificator = json_decode($client->unique_indentificator);

                if ($uniqueIdentificator->id == $data['unique_indentificator']->id) {
                    if ($uniqueIdentificator->value == $data['unique_indentificator']->value) {
                        $isMatchUniqueIdentificator = true;
                    }
                }

                $informationData = json_decode($client->information_data);
                $countPreloadData = count($data['information_data']);
                $countMatchInformationData = 0;

                foreach ($informationData as $field) {
                    foreach ($data['information_data'] as $preloadField) {
                        if ($field->id == $preloadField->id) {
                            if ($field->value == $preloadField->value) {
                                $countMatchInformationData++;
                                break;
                            } else {
                                continue;
                            }
                        } else {
                            continue;
                        }
                    }
                }
                if ($countMatchInformationData == $countPreloadData) {
                    $isMatchInformationData = true;
                }

                if ($isMatchUniqueIdentificator || $isMatchInformationData) {
                    $found = true;
                }

                return $found;
            })->first();
        }

        return $clientNew;
    }

    public function updateOrCreateClient(array $data) 
    {
        $clientExists = $this->findClient([
            'form_id' => $data['form_id'],
            'unique_indentificator' => $data['unique_indentificator']
        ]);

        if($clientExists && isset($clientExists->id)) {
            $clientExists = $this->updateClient($clientExists, $data['information_data']);
        } else {
            $clientExists = $this->storeNewClient($data);
        }

        return $clientExists;
    }

    public function updateClient($client, array $informationData)
    {
        $client->information_data = $this->formatInformationData($informationData);
        $client->save();

        return $client;
    }

    public function storeNewClient(array $data)
    {
        $client = ClientNew::create([
            "form_id" => $data['form_id'],
            "information_data" => $this->formatInformationData($data['information_data']),
            "unique_indentificator" => json_encode($data['unique_indentificator']),
        ]);

        return $client;
    }

    private function formatInformationData(array $informationData) : string
    {
        $informationDataClient = [];

        foreach($informationData as $value){
            array_push($informationDataClient, (Object) [
                "id" => $value->id,
                "value" => $value->value,
            ]);
        }

        return json_encode($informationDataClient);
    }
}
