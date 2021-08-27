<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\KeyValue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use App\Http\Controllers\FormController;


class ClientImport implements ToModel, WithBatchInserts, WithValidation
{
    //variable que almacena las validaciones para cada uno de los clientes cargados en el archivo
    private $rules;

    public function __construct(array $idFIeldsColumns, int $formId)
    {
        $this->rules=$this->searchValidationsElementsInField($idFIeldsColumns,$formId);
    }


    /*public function prepareForValidation($data, $index)
    {
        $data['email'] = $data['email'] ?? 0;

        return $data;
    }*/

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
        /*if (is_numeric($row[4]) || $row[4] == null) {
            $client = Client::where('document', $row[5])->first();
            if ($client == null) {
                return new Client([
                    'first_name' => $row[0],
                    'middle_name' => $row[1],
                    'first_lastname' => $row[2],
                    'second_lastname' => $row[3],
                    'document_type_id' => !empty($row[4]) ? $row[4] : 1,
                    'document' => $row[5],
                    'phone' => $row[6],
                    'email' => $row[7],
                ]);
            } else {
                $client->first_name = $row[0];
                $client->middle_name = $row[1];
                $client->first_lastname = $row[2];
                $client->second_lastname = $row[3];
                $client->document_type_id = $row[4];
                $client->document = $row[5];
                $client->phone = $row[6];
                $client->email = $row[7];
                $client->save();
            }
        }*/
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
    private function searchValidationsElementsInField($searchIdFileds,$formId){
        $FormController= new FormController();
        $sections=$FormController->getSections($formId);
        $validation=[];
        if(count($sections)>0){
            foreach($sections as $section){
                foreach(json_decode($section->fields) as $field){
                    foreach($searchIdFileds as $search){
                        if($search->idField==$field->id){
                            $rules= isset($field->required) ? 'required' : '';
                            $rules.= '|'.$this->kindOfValidationType($field->type);
                            $rules.= isset($field->minLength) ? '|min:'.$field->minLength : '';
                            $rules.= isset($field->maxLength) ? '|max:'.$field->maxLength : '';
                            $validation[$search->column]=$rules;
                        }
                    }
                }
            }
            \Log::info($validation);
            return $validation;
        }else{
            return "No se encuentran secciones para el form id ".$formId;
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
}
