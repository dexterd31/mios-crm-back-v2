<?php

namespace App\Managers;

use App\Models\ClientNew;
use App\Models\CustomerDataPreload;
use App\Models\CustomFieldData;
use App\Models\Directory;
use App\Models\RelAdvisorClientNew;

class DataBaseManager
{
    /**
     * Retorna una lista de clientes y las columnas a mostrar.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $formId
     * @param array $filterOptions
     *  - tags: lista de tags, con las que filtraran los clientes.
     *  - fromDate: fecha de inicial.
     *  - toDate: fecha final.
     * @return array
     */
    public function listManagement(int $formId, array $filterOptions = []) : array
    {
        $clients = ClientNew::formFilter($formId);

        if (isset($filterOptions['tags']) && count($filterOptions['tags'])) {
            $clients->join('client_tag', 'client_tag.client_id', 'client_news.id')
            ->whereIn('client_tag.tag_id', $filterOptions['tags']);  
        }
        if (isset($filterOptions['fromDate']) && isset($filterOptions['toDate'])) {
            $clients->updatedAtBetweenFilter($filterOptions['fromDate'], $filterOptions['toDate']);
        }

        $tableColumns = [];
        $clients->get(['client_news.id', 'client_news.updated_at', 'information_data', 'unique_indentificator'])
        ->map(function ($client) use ($tableColumns) {
            $informationData = json_decode($client->information_data);
            $client->information_data = $informationData[0]->value;
            $uniqueIndentificator = json_decode($client->unique_indentificator);
            $client->unique_indentificator = $uniqueIndentificator[0]->value;
            if (!count($tableColumns)) {
                $tableColumns = [$informationData[0]->id, $uniqueIndentificator[0]->id];
            }
        });

        return [$clients, $tableColumns];
    }

    public function createClients()
    {
        $clientsManager = new ClientsManager;
        $customerDataPreload = CustomerDataPreload::take(200);
        $customerDataPreloadIds = clone $customerDataPreload->pluck('id');
        $customerDataPreload = $customerDataPreload->get();

        
        foreach ($customerDataPreload as $customerData) {
            $data = [
                "form_id" => $customerData->form_id,
                "unique_indentificator" => $customerData->unique_identificator,
                "information_data" => $customerData->customer_data
            ];

            $client = $clientsManager->findClientByCustomerDataPreload($customerData);

            if ($client && $customerData->to_update) {
                $client = $clientsManager->updateClient($client, $data["information_data"]);
                $customerData->delete();
                $saveDirectories = $this->addToDirectories($customerData->form_answer, $customerData->form_id, $client->id, $customerData->customer_data, $customerData->adviser);
            } else if (is_null($client)) {
                $client = $clientsManager->storeNewClient($data);
                $saveDirectories = $this->addToDirectories($customerData->form_answer, $customerData->form_id, $client->id, $customerData->customer_data, $customerData->adviser);
            }

            if ($customerData->custom_field_data) {
                $customFieldData = CustomFieldData::clientFilter($client->id)->first();
    
                if ($customFieldData) {
                    foreach ($customerData->custom_field_data as $fieldData) {
                        $customFieldData->field_data[] = $fieldData;
                    }
                    $customFieldData->save();
                } else {
                    CustomFieldData::create([
                        'client_new_id' => $client->id,
                        'field_data' => $customerData->custom_field_data
                    ]);
                }
            }

            if (count($customerData->tags)) {
                $client->tags()->attach($customerData->tags);
            }

            if ($customerData->imported_file_id) {
                $client->importedFiles()->attach([$customerData->imported_file_id]);
            }

            if ($customerData->adviser){
                $relAdvisorClientNew = RelAdvisorClientNew::where('client_new_id', $client->id)->where('rrhh_id', $customerData->adviser)->first();
    
                if (is_null($relAdvisorClientNew)) {
                    $relAdvisorClientNew = RelAdvisorClientNew::create([
                        'client_new_id' => $client->id,
                        'rrhh_id' => $customerData->adviser
                    ]);
                }
                
            }
        }

        CustomerDataPreload::destroy($customerDataPreloadIds);
    }

    /**
     * Crea o actualiza un registro en la tabla directories
     * @author Edwin David Sanchez Babin <e.sanchez@montechelo.com.co> 
     * 
     * @param array $data
     * @param int $formId
     * @param int $clientId
     * @param int $clientNewId
     * @param array $indexForm
     * @param int $rrhhId
     * @return mixed
     */
    private function addToDirectories(array $data,int $formId,int $clientNewId, array $indexForm, int $rrhhId){
        $newDirectory = Directory::updateOrCreate([
            'form_id' => $formId,
            'client_new_id' => $clientNewId,
            'data' => json_encode($data)

        ],[
            'rrhh_id' => $rrhhId,
            'form_index' => json_encode($indexForm)
        ]);

        return $newDirectory;
    }
}
