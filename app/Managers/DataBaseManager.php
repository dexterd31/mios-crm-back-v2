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


        $data = [
            "form_id" => $customerDataPreload->form_id,
            "unique_indentificator" => $customerDataPreload->unique_identificator,
            "information_data" => $customerDataPreload->customer_data
        ];

        foreach ($customerDataPreload as $customerData) {
            $client = $clientsManager->findClientByCustomerDataPreload($customerData);

            if ($client && $customerDataPreload->to_update) {
                $client = $clientsManager->updateClient($client, $data["information_data"]);
                $customerDataPreload->delete();
                $saveDirectories = $this->addToDirectories($customerDataPreload->form_answer, $customerDataPreload->form_id, $client->id, $customerDataPreload->customer_data);
            } else if (is_null($client)) {
                $client = $clientsManager->storeNewClient($data);
                $saveDirectories = $this->addToDirectories($customerDataPreload->form_answer, $customerDataPreload->form_id, $client->id, $customerDataPreload->customer_data);
            }

            if ($customerDataPreload->custom_field_data) {
                $customFieldData = CustomFieldData::clientFilter($client->id)->first();
    
                if ($customFieldData) {
                    foreach ($customerDataPreload->custom_field_data as $fieldData) {
                        $customFieldData->field_data[] = $fieldData;
                    }
                    $customFieldData->save();
                } else {
                    CustomFieldData::create([
                        'client_id' => $client->id,
                        'field_data' => $customerDataPreload->custom_field_data
                    ]);
                }
            }

            if (count($customerDataPreload->tags)) {
                $client->tags()->attach($customerDataPreload->tags);
            }

            if ($customerDataPreload->imported_file_id) {
                $client->importedFiles()->attach([$customerDataPreload->imported_file_id]);
            }

            if ($customerDataPreload->adviser){
                $relAdvisorClientNew = RelAdvisorClientNew::where('client_new_id', $client->id)->where('rrhh_id', $customerDataPreload->adviser)->first();
    
                if (is_null($relAdvisorClientNew)) {
                    $relAdvisorClientNew = RelAdvisorClientNew::create([
                        'client_new_id' => $client->id,
                        'rrhh_id' => $customerDataPreload->adviser
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
     * @return mixed
     */
    private function addToDirectories(array $data,int $formId,int $clientNewId, array $indexForm){
        $newDirectory = Directory::updateOrCreate([
            'form_id' => $formId,
            'client_new_id' => $clientNewId,
            'data' => json_encode($data)

        ],[
            'rrhh_id' => auth()->user()->rrhh_id,
            'form_index' => json_encode($indexForm)
        ]);

        return $newDirectory;
    }
}
