<?php

namespace App\Managers;

use App\Jobs\CreateClients;
use App\Models\ClientNew;
use App\Models\ClientTag;
use App\Models\CustomerDataPreload;
use App\Models\CustomFieldData;
use App\Models\Directory;
use App\Models\ImportedFileClient;
use App\Models\RelAdvisorClientNew;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
        
        if (isset($filterOptions['from_date']) && isset($filterOptions['to_date'])) {
            $clients->updatedAtBetweenFilter($filterOptions['from_date'], $filterOptions['to_date']);
        }
        if (isset($filterOptions['tags']) && count($filterOptions['tags'])) {
            $clients->join('client_tag', 'client_tag.client_new_id', 'client_news.id')
            ->whereIn('client_tag.tag_id', $filterOptions['tags']);
        }
        
        $tableColumns = [];
        $clients = $clients->distinct()->get(['client_news.id', 'client_news.updated_at', 'information_data', 'unique_indentificator'])
        ->map(function ($client) use (&$tableColumns) {
            $informationData = json_decode($client->information_data);
            $uniqueIndentificator = json_decode($client->unique_indentificator);
            $client->information_data = $informationData[0]->value;
            $client->unique_indentificator = $uniqueIndentificator->value;

            if (!count($tableColumns)) {
                $tableColumns = [
                    'information_data' => $informationData[0]->id,
                    'unique_indentificator' => $uniqueIndentificator->id
                ];
            }

            return $client;
        });

        return [$clients, $tableColumns];
    }

    /**
     * Crea clientes masivamente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return void
     */
    public function createClients()
    {
        $clientsManager = new ClientsManager;
        $customerDataPreload = CustomerDataPreload::take(1000);
        if ($customerDataPreload) {
            $customerDataPreloadIds = clone $customerDataPreload->pluck('id');
            $customerDataPreload = $customerDataPreload->get();
            
            foreach ($customerDataPreload as $customerData) {

                $formAnswer = $customerDataPreload->form_answer;
                $sections = $customerDataPreload->form->section;
                $formAnswers = [];

                foreach ($sections as $section) {
                    foreach (json_decode($section->fields) as $field) {
                        foreach ($formAnswer as $fieldData) {
                            if (isset($fieldData[$field->id])) {
                                $field->value = $fieldData[$field->id];
                                $formAnswers[] = $field; 
                            }
                        }
                    }
                }

                $customerData->form_answer = $formAnswers;

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
                        $fieldDataArray = $customFieldData->field_data;
                        foreach ($customerData->custom_field_data as $fieldData) {
                            $fieldDataArray[] = $fieldData;
                        }
                        $customFieldData->field_data = $fieldDataArray;
                        $customFieldData->save();
                    } else {
                        CustomFieldData::create([
                            'client_new_id' => $client->id,
                            'field_data' => $customerData->custom_field_data
                        ]);
                    }
                }
    
                if (!is_null($customerData->tags) && count($customerData->tags)) {
                    $clientTags = $client->tags()->pluck('tags.id')->toArray();
                    if (count($clientTags)) {
                        foreach ($customerData->tags as $tag) {
                            if (!in_array($tag, $clientTags)) {
                                ClientTag::create([
                                    'client_new_id' => $client->id,
                                    'tag_id' => $tag
                                ]);
                            }
                        }
                    } else {
                        foreach ($customerData->tags as $tag) {
                            ClientTag::create([
                                'client_new_id' => $client->id,
                                'tag_id' => $tag
                            ]);
                        }
                    }
                }
    
                if ($customerData->imported_file_id) {
                    $importedFileClient = ImportedFileClient::clientFilter($client->id)
                    ->importedFileFilter($customerData->imported_file_id)->first();
    
                    if (!is_null($importedFileClient)) {
                        ImportedFileClient::create([
                            'client_new_id' => $client->id,
                            'imported_file_id' => $customerData->imported_file_id
                        ]);
                    }
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
    
            CustomerDataPreload::destroy($customerDataPreloadIds->toArray());
        }

        dispatch((new CreateClients)->delay(Carbon::now()->addSeconds(1)))->onQueue('create-clients');

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
