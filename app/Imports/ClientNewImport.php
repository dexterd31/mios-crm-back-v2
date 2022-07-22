<?php

namespace App\Imports;

use App\Models\Attachment;
use App\Models\CustomerDataPreload;
use App\Models\Section;
use Illuminate\Support\Carbon;
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
    private $formId;
    private $toUpdate;
    private $assignUsers;
    private $fieldsLoad;
    private $resume;
    private $uploadController;
    private $tags;
    private $customFields;
    private $importedFileId;

    public function __construct(UploadController $uploadController = null, $formId = 0, $toUpdate = false, $fieldsLoad = [], $assignUsers = null, $tags = [], $customFields = [], $importedFileId = 0)
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
        $fieldsToValidator = [];
        $foundFields = [];
        $fieldRules = [];
        $fieldTypes = [];
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

        $this->chunkCounter++;
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

    private function makeValidation($field)
    {
        $rules = '';

        if (isset($field->required) && $field->required) {
            $rules = 'required';

            if (isset($field->minLength) && isset($field->maxLength)) {
                $minLength = $field->minLength;
                $maxLength = $field->maxLength;
                if ($field->type == 'number') {
                    $minLen = "0";
                    $maxLen = "";
                    $minLen .= str_repeat("0", intval($field->minLength) - 1);
                    $maxLen .= str_repeat("9", intval($field->maxLength));
                    $minLength = $minLen;
                    $maxLength = $maxLen;
                }
                $rules .= "|min:$minLength";
                $rules .= "|max:$maxLength";
            }
            $rules.= "|{$this->setValidationType($field->type)}";
        }

        $fieldValidator = str_replace(['.','-','*',','],'',$field->label);

        return [$fieldValidator, $rules];
    }

    private function setValidationType($type)
    {
        $validationType = 'string';

        switch($type){
            case "email":
                $validationType = "email";
                break;
            case "number":
                $validationType = "numeric";
                break;
            case "date":
                $validationType = "date|date_format:Y-m-d";
                break;
        }

        return $validationType;
    }

    private function castData($type, $data)
    {
        switch($type){
            case "number":
                $data = intval(trim($data));
                break;
            default:
                $data = strval($data);
                break;
        }

        return $data;
    }

    private function structureCustomerData($field, $data)
    {
        $answer = new stdClass();
        $field->value = $data;
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
            "id" => $field->id,
            "key" => $field->key,
            "preloaded" => $field->preloaded,
            "label" => $field->label,
            "isClientInfo" => $field->isClientInfo,
            "client_unique" => isset($field->client_unique) ? $field->client_unique : false,
            "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value,
            "controlType" => $field->controlType,
            "type" => $field->type
        ];
        $answer->formAnswerIndex = (Object)[
            "id" => $field->id,
            "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
        ];
        $answer->success=true;
        $answer->Originalfield=$field;
        
        return $answer;
    }

    private function findFieldStructure($formId, $fieldId)
    {
        $fieldExist = true;
        $fields = json_decode(Section::where('form_id', $formId)
        ->whereJsonContains('fields', ['id' => $fieldId])
        ->first()->fields);

        if(count($fields) == 0){
            $fieldExist = false;
        }
        $field = collect($fields)->filter(function($x) use ($fieldId){
            return $x->id == $fieldId;
        })->first();
        if(empty($field)){
            $fieldExist = false;
        }

        return $fieldExist ? $field : $fieldExist;
    }

    private function verifyValueInFieldParameters($field, $value)
    {
        $isValid = false;
        $message = 'Value is not valid';
        if(($field->controlType == 'dropdown' || $field->controlType == 'autocomplete' || $field->controlType == 'radiobutton')){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                if(intval($value) == 0){
                    return $x->name == $value;
                }
                return $x->id == $value;
            })->first();
            if($field_name){
                $isValid = true;
                $value = $field_name->id;

            }
            $message = "Value $value not match";
        }elseif($field->controlType == 'datepicker'){
            if($value != "Invalid date"){
                $date = "";
                try {
                    if(is_int($value)){
                       //Se suma un dia pues producción le resta un dia a las fechas formato date de excel
                        $unix_date = (($value+1) - 25569) * 86400;
                        $date = Carbon::createFromTimestamp($unix_date)->format('Y-m-d');
                    }else{
                        $date = Carbon::parse(str_replace("/","-",$value))->format('Y-m-d');
                    }
                    $isValid = true;
                    $value = $date;
                }catch (\Exception $ex){
                    $isValid = false;
                    $message = "Date $value is not a valid format";
                }
            }else{
                $isValid = true;
                $value = '';
            }
        }elseif($field->controlType == 'file'){
            $attachment = Attachment::where('id',$value)->first();
            $isValid = true;
            $value = url() . '/api/attachment/downloadFile/'.$attachment->id;
        }elseif($field->controlType == 'multiselect'){
            $multiAnswer=[];
            foreach($value as $val){
                $field_name = collect($field->options)->filter(function($x) use ($val){
                    return $x->id == $val;
                })->first();
                if (is_null($field_name)) {
                    continue;
                } else {
                    $field_name = $field_name->name;
                }
                array_push($multiAnswer, $field_name);
            }
            $isValid = true;
            $value = implode(",", $multiAnswer);
        }elseif($field->controlType == 'currency'){
            $isValid = true;
            $value = str_replace(",", "", $value);
        }else{
            $isValid = true;
            $value = $value;
        }

        return [$isValid, $value, $message];
    }
}
