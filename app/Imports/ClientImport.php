<?php

namespace App\Imports;

use App\Models\ClientNew;
use App\Http\Controllers\ClientNewController;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class ClientImport implements ToModel, WithBatchInserts, WithValidation, WithHeadingRow
{
    //variable que almacena las validaciones para cada uno de los clientes cargados en el archivo
    private $formId;
    private $rules;
    private $asigns;
    private $sections;
    private $uniqueClientIdentificator;

    public function __construct(array $idFIeldsColumns, int $formId, array $sections, object $uniqueClientIdentificator)
    {
        $this->formId=$formId;
        $this->sections = $sections;
        $this->uniqueClientIdentificator= $uniqueClientIdentificator;
        $this->asigns = $this->bringTypeOfField($idFIeldsColumns);
        $this->rules = $this->searchValidationsElementsInField($idFIeldsColumns);
        HeadingRowFormatter::default('none');
    }

    /**
     * @desc
     *
     */
    public function rules(): array
    {
        return $this->rules;
    }


    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $informationClient=[];
        $uniqueIdentificator=new \stdClass();
        foreach($this->asigns as $index=>$field){
            $client=$field;
            $client->value=$row[$client->columnName];
            if(isset($client->isClientInfo)){
                array_push($informationClient,$client);
            }
            if(isset($client->isClientUnique)){
                $uniqueIdentificator=$client;
            }
        }
        $clientController=new ClientNewController();
        $newRequest = new Request();
        $newRequest->replace([
            "form_id" => $this->formId,
            "information_data" => json_encode($informationClient),
            "unique_indentificator" => json_encode($uniqueIdentificator),
        ]);
        $client=$clientController->create($newRequest);
        return $client;

    }

    public function batchSize(): int
    {
        return 1000;
    }

     /**
     * @desc Busca los fields por su id en las secciones
     * @param array $search Arreglo de objetos, cada objeto debe contener los elementos idField: numero del field al que pertenece
     * @param integer $formId Numero entero con el id del formulario al que se le debe realizar la busqueda de fields
     * @return array Arreglo de objetos, se devuelve el mismo objeto solo que le agregamos LOS ELEMENTOS type, required, minLength y maxLength
     * @author Leonardo Giraldo Quintero
     */
    private function searchValidationsElementsInField($searchIdFields){
        $validation=[];
        if(count($this->sections)>0){
            foreach($this->sections as $section){
                foreach($section->fields as $field){
                    foreach($searchIdFields as $search){
                        if($search->id==$field->id){
                            $rules= isset($field->required) ? 'required' : '';
                            $rules.= '|'.$this->kindOfValidationType($field->type);
                            $rules.= isset($field->minLength) ? '|min:'.$field->minLength : '';
                            $rules.= isset($field->maxLength) ? '|max:'.$field->maxLength : '';
                            //unset($search->columnName);
                        }
                    }
                }
            }
            return $validation;
        }
    }


    private function kindOfValidationType($type){
        $answer='';
        switch($type){
            case "email":
                $answer="email";
            break;
            case "number":
                $answer="numeric";
            break;
            case "date":
                $answer="date";
            break;
            default:
                $answer="string";
            break;

        }
        return $answer;
    }

    private function bringTypeOfField($searchIdFileds){
        $completeFileds=[];
        if(count($this->sections)>0){
            foreach($this->sections as $section){
                foreach($section->fields as $field){
                    foreach($searchIdFileds as $search){
                        if($search->id==$field->id){
                            $search->key=$field->key;
                            $search->preloaded=$field->preloaded;
                            $search->label=$field->label;
                            $search->isClientInfo=$field->isClientInfo;
                            if($this->uniqueClientIdentificator->id == $search->id){
                                $search->isClientUnique=$field->isClientInfo;
                            }
                            $completeFileds[$search->columnName]=$search;
                        }
                    }
                }
            }
            return $completeFileds;
        }
    }
}
