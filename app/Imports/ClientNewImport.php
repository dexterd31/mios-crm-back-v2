<?php

namespace App\Imports;

use App\Http\Controllers\UploadController;
use App\Models\CustomerDataPreload;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;


class ClientNewImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    private $formId;
    private $toUpdate;
    private $assignUsers;
    private $fieldsLoad;
    private $resume;
    private $uploadController;

    public function __construct(UploadController $uploadController = null, $formId = 0, $toUpdate = false, $fieldsLoad = [], $assignUsers = null)
    {
        HeadingRowFormatter::default('none');
        $this->uploadController = $uploadController;
        $this->formId = $formId;
        $this->toUpdate = $toUpdate;
        $this->fieldsLoad = $fieldsLoad;
        $this->assignUsers = $assignUsers;
        $this->resume = ['cargados' => 0, 'errores' => [], 'nocargados' => 0, 'totalRegistros' => 0];
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $rowIndex => $row) {
            $answerFields = (Object)[];
            $errorAnswers = [];
            $formAnswerClient=[];
            $customFieldData = [];
    
            foreach ($row as $fieldIndex => $field) {
                if (isset($this->fieldsLoad[$fieldIndex])) {
                    $dataValidate = $this->uploadController->validateClientDataUpload($this->fieldsLoad[$fieldIndex], $field, $this->formId);
        
                    if ($dataValidate->success) {
                        foreach ($dataValidate->in as $in) {
                            if (!isset($answerFields->$in)) {
                                $answerFields->$in = [];
                            }
                            
                            array_push($answerFields->$in, $dataValidate->$in);
                        }
                        
                        array_push($formAnswerClient, $dataValidate->formAnswer);
                    } else {
                        $fila = strval(intval($rowIndex) + 1);
                        $columnErrorMessage = "Error en la Fila $fila";
                        array_push($dataValidate->message, $columnErrorMessage);
                        array_push($errorAnswers, $dataValidate->message);
                    }
                }
            }
    
            if (!count($errorAnswers)) {
                $uniqueIdentificator = $answerFields->uniqueIdentificator[0];
    
                $rrhhId = 0;
                
                if ($this->assignUsers) {
                    [$this->assignUsers, $rrhhId] = $this->assignUsers($this->assignUsers, $uniqueIdentificator, $this->formId);
                };
                
                CustomerDataPreload::create([
                    'form_id' => $this->formId,
                    'customer_data' => $answerFields->informationClient,
                    'to_update' => filter_var($this->toUpdate, FILTER_VALIDATE_BOOLEAN),
                    'adviser' => $rrhhId,
                    'unique_identificator' => $uniqueIdentificator,
                    'form_answer' => $formAnswerClient
                ]);
    
                $this->resume['cargados']++;
                
            } else {
                $this->resume['errores'][] = $errorAnswers;
                $this->resume['nocargados']++;
            }
    
            $this->resume['totalRegistros']++;
        }
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    public function batchSize(): int
    {
        return 5000;
    }

    public function getResume()
    {
        return (object) $this->resume;
    }

    /**
     * Asigna los registros a los usuarios insertando en la tabla customer_data_preloads
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     * 
     * @param $assignUsersObject
     * @param $clienId
     * @return mixed
     */
    private function assignUsers($assignUsersObject, $uniqueIndentificator, $formId)
    {
        $advisers = $assignUsersObject->advisers;
        $advisersIndex = $assignUsersObject->advisersIndex;
        $quantity = $assignUsersObject->quantity;
        $rrhhId = $advisers[$advisersIndex]['id_rhh'];

        $customerDataPreload = CustomerDataPreload::where('form_id', $formId)->where('adviser', $rrhhId)
            ->whereJsonContains("unique_identificator",["id"=>$uniqueIndentificator->id]);

        $uniqueValueInt = intval($uniqueIndentificator->value);

        if (gettype($uniqueValueInt) == 'integer') {
            $customerDataPreload->where(function ($query) use ($uniqueValueInt,$uniqueIndentificator) {
                $query->whereJsonContains("unique_identificator",["value" => $uniqueIndentificator->value])
                ->orWhereJsonContains("unique_identificator",["value" => $uniqueValueInt]);
            });
        } else {
            $customerDataPreload->whereJsonContains("unique_identificator",["value" => $uniqueIndentificator->value]);
        }

        $customerDataPreload = $customerDataPreload->first();

        if (!isset($customerDataPreload->id)) {
            $assignUsersObject->quantity++;
        } else {
            $rrhhId = 0;
            $assignUsersObject->quantity++;
        }

        if($advisers[$advisersIndex]['quantity'] == $quantity){
            $assignUsersObject->advisersIndex++;
            $assignUsersObject->quantity = 0;
        }

        return [$assignUsersObject, $rrhhId];
    }
}
