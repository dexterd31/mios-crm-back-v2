<?php

namespace App\Managers;

use App\Models\ClientNew;
use App\Models\CustomerDataPreload;

class ClientsManager
{
    /**
     * Busca un cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param array $data
     * @return App\Models\ClientNew
     */
    public function findClient(array $data)
    {
        $clientNew = new ClientNew;
        
        if (isset($data['client_new_id'])){
            $clientNew = $clientNew->find($data['client_new_id']);

        } else {
            $clientNew = $clientNew::where("form_id", $data['form_id'])->latest()->get()
            ->filter(function ($client) use ($data) {
                $found = false;
                $isMatchUniqueIdentificator = false;
                $isMatchInformationData = false;
                if (isset($data['unique_indentificator'])) {
                    $uniqueIdentificator = json_decode($client->unique_indentificator);
                    if (gettype($data['unique_indentificator']) == 'string') {
                        $dataUniqueIdentificator = json_decode($data['unique_indentificator']);
                    } else {
                        $dataUniqueIdentificator = $data['unique_indentificator'];
                    }
                    
                    if ($uniqueIdentificator->id == $dataUniqueIdentificator->id) {
                        if ($uniqueIdentificator->value == $dataUniqueIdentificator->value) {
                            $isMatchUniqueIdentificator = true;
                        }
                    }
                }

                if (isset($data['information_data'])) {
                    $informationData = json_decode($client->information_data);
                    $countInformationData = count($data['information_data']);
                    $countMatchInformationData = 0;
    
                    foreach ($informationData as $field) {
                        foreach ($data['information_data'] as $preloadField) {
                            $preloadField = (object) $preloadField;
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
                    if ($countMatchInformationData == $countInformationData) {
                        $isMatchInformationData = true;
                    }
                }

                if ($isMatchUniqueIdentificator || $isMatchInformationData) {
                    $found = true;
                }

                return $found;
            })->first();
        }

        return $clientNew;
    }

    /**
     * Actualiza o crea un cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param array $data
     * @return App\Models\ClientNew
     */
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

    /**
     * Actualia un cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param App\Models\ClientNew $client
     * @param array $informationData
     * @return App\Models\ClientNew
     */
    public function updateClient($client, array $informationData)
    {
        $client->information_data = $this->formatInformationData($informationData);
        $client->save();

        return $client;
    }

    /**
     * Crea un cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param array $data
     * @return App\Models\ClientNew
     */
    public function storeNewClient(array $data)
    {
        $client = ClientNew::create([
            "form_id" => $data['form_id'],
            "information_data" => $this->formatInformationData($data['information_data']),
            "unique_indentificator" => json_encode($data['unique_indentificator']),
        ]);

        return $client;
    }

    /**
     * Da formato a la infomacion del cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param array $informationData
     * @return string
     */
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

    /**
     * Busca un cliente segun los datos precargados, lo actualiza si es necesario y si no lo encuentra lo crea.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param App\Models\CustomerDataPreload $customerDataPreload
     * @return App\Models\ClientNew
     */
    public function findClientByCustomerDataPreload(CustomerDataPreload $customerDataPreload)
    {
        return ClientNew::where('form_id', $customerDataPreload->form_id)->get()
            ->filter(function ($client) use ($customerDataPreload) {
                $isMatchUniqueIdentificator = false;
                $isMatchInformationData = false;
                $uniqueIdentificator = json_decode($client->unique_indentificator);

                if ($uniqueIdentificator->id == $customerDataPreload->unique_identificator->id) {
                    if ($uniqueIdentificator->value == $customerDataPreload->unique_identificator->value) {
                        $isMatchUniqueIdentificator = true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }

                if ($isMatchUniqueIdentificator) {
                    $informationData = json_decode($client->information_data);
                    $countPreloadData = count($customerDataPreload->customer_data);
                    $countMatchInformationData = 0;

                    foreach ($informationData as $field) {
                        foreach ($customerDataPreload->customer_data as $preloadField) {
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
                }

                return $isMatchInformationData;
            })->first();
    }
}
