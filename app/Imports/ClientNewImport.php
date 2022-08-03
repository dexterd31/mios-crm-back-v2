<?php

namespace App\Imports;

use App\Models\CustomerDataPreload;
use App\Traits\FindAndFormatValues;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use stdClass;

class ClientNewImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use FindAndFormatValues;

    private $formId;
    private $toUpdate;
    private $assignUsers;
    private $fieldsLoad;
    private $resume;
    private $uploadController;
    private $tags;
    private $customFields;
    private $importedFileId;

    public function __construct($formId = 0, $toUpdate = false, $fieldsLoad = [], $assignUsers = null, $tags = [], $customFields = [], $importedFileId = 0)
    {
        HeadingRowFormatter::default('none');
        $this->formId = $formId;
        $this->toUpdate = $toUpdate;
        $this->fieldsLoad = $fieldsLoad;
        $this->assignUsers = $assignUsers;
        $this->resume = ['cargados' => 0, 'errores' => [], 'nocargados' => 0, 'totalRegistros' => 0];
        $this->tags = $tags;
        $this->customFields = $customFields;
        $this->importedFileId = $importedFileId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $rowIndex => $row) {
            $answerFields = (Object)[];
            $errorAnswers = [];
            $formAnswerClient = [];
            $customFieldData = [];
    
            foreach ($row as $fieldIndex => $field) {
                if (isset($this->fieldsLoad[$fieldIndex])) {
                    $dataValidate = $this->validateClientDataUpload($this->fieldsLoad[$fieldIndex], $field, $this->formId);
        
                    if ($dataValidate->success) {
                        foreach ($dataValidate->in as $in) {
                            if (!isset($answerFields->$in)) {
                                $answerFields->$in = [];
                            }
                            
                            array_push($answerFields->$in, $dataValidate->$in);
                        }
                        
                        $formAnswerClient[$dataValidate->formAnswer->id] = $dataValidate->formAnswer->value;
                    } else {
                        $fila = strval(intval($rowIndex) + 1);
                        $columnErrorMessage = "Error en la Fila $fila";
                        array_push($dataValidate->message, $columnErrorMessage);
                        array_push($errorAnswers, $dataValidate->message);
                    }
                }

                if (count($this->customFields)) {
                    if (isset($this->customFields[$fieldIndex])) {
                        $customFieldData[] = ['id' => $this->customFields[$fieldIndex], 'value' => $field];
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
                    'form_answer' => $formAnswerClient,
                    'custom_field_data' => count($this->customFields) ? $customFieldData : [],
                    'tags' => $this->tags,
                    'imported_file_id' => $this->importedFileId
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

    public function validateClientDataUpload($field,$data,$formId = null){
        $answer=new stdClass();
        $answer->success=false;
        $answer->message=[];
        if($formId != null){
            $formatValue = $this->findAndFormatValues($formId,$field->id,$data);
            if($formatValue->valid){
                $data = $formatValue->value;
            }else{
                array_push($answer->message,$formatValue->message." in ".$field->label);
                return $answer;
            }
        }
        $rules = '';
        $validationType = $this->kindOfValidationType($field->type,$data);
        if(isset($field->required) && $field->required){
            $rules = 'required';
            if(isset($field->minLength) && isset($field->maxLength)){
                $minLength = $field->minLength;
                $maxLength = $field->maxLength;
                if($field->type == 'number'){
                    $minLen = "0";
                    $maxLen = "";
                    $minLen .= str_repeat("0", intval($field->minLength) - 1);
                    $maxLen .= str_repeat("9", intval($field->maxLength));
                    $minLength = $minLen;
                    $maxLength = $maxLen;
                }
                $rules.= '|min:'.$minLength;
                $rules.= '|max:'.$maxLength;
            }
            $rules.= '|'.$validationType->type;
        }
        $fieldValidator = str_replace(['.','-','*',','],'',$field->label);
        $validator = Validator::make([$fieldValidator=>$validationType->formatedData], [
            $fieldValidator => $rules
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $message) {
                array_push($answer->message,$message." in ".$field->label);
            }
        }else{
            $field->value=$validationType->formatedData;
            $answer->in=[];
            if(isset($field->isClientInfo) && $field->isClientInfo){
                $answer->informationClient= (object)[
                    "id" => $field->id,
                    "value" => $field->value
                ];
                array_push($answer->in,'informationClient');
            }
            if(isset($field->client_unique) && $field->client_unique){
                $answer->uniqueIdentificator = (Object)[
                    "id" => $field->id,
                    "key" => $field->key,
                    "preloaded" => $field->preloaded,
                    "label" => $field->label,
                    "isClientInfo" => $field->isClientInfo,
                    "client_unique" => $field->client_unique,
                    "value" => $field->value
                ];
                array_push($answer->in,'uniqueIdentificator');
            }
            if(isset($field->preloaded) && $field->preloaded){
                $answer->preload=[
                    "id" => $field->id,
                    "key" => $field->key,
                    "value" => $field->value
                ];
                array_push($answer->in,'preload');
            }
            $answer->formAnswer = (Object)[
                'id' => $field->id,
                'value' => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
            ];
            $answer->formAnswerIndex = (Object)[
                $field->id => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
            ];
            $answer->success=true;
            $answer->Originalfield=$field;
        }
        return $answer;
    }

    private function kindOfValidationType($type,$data){
        $answer = new stdClass();
        $answer->formatedData = $data;
        switch($type){
            case "email":
                $answer->type = "email";
                break;
            case "options":
            case "number":
                $answer->type = "numeric";
                $answer->formatedData = intval(trim($data));
                break;
            case "date":
                $answer->type = "date|date_format:Y-m-d";
                break;
            default:
                $answer->type = "string";
                $answer->formatedData = strval($data);
                break;

        }
        return $answer;
    }
}
