<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Directory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use App\Models\KeyValue;
use Helpers\FormAnswerHelper;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class FormAnswerImport implements ToModel, WithBatchInserts
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public $userId;
    public $formId;
    public $headers;
    public $ids;
    public $num = 0;
    public $sections = [];
    private $rows_count = 0;

    public function __construct($userId, $formId, $ids)
    {
        $this->userId = $userId;
        $this->formId = $formId;
        $this->ids = $ids;
    }


    public function model(array $row)
    {

        $formAnswerHelper = new FormAnswerHelper();
        if ($this->num == 0) {
            $this->headers = $row;
            $this->num = $this->num + 1;
        } else {
            $this->sections = [];
            // Obtener el id del cliente
            $client = Client::where('document', $row[5])->select('id')->first();
            // Se pasan los labels para obtener los keyvalues del formulario?
            //Se deben pasar los id's  y comparar contra las secciones
            $keyValues = $formAnswerHelper->getKeysValuesForExcel($this->headers, $this->formId, $this->ids);
          
            // Todo el proceso de independiso en el metodo buildDocumentExcel
            $this->buildDocumentExcel($keyValues,$row,$client); 

            // Se normaliza el objeto de FormAnswer
            $formAnswer = $formAnswerHelper->structureAnswer($this->formId, $this->sections, $this->ids); 
            $this->rows_count++;

            // Se crea el objecto para guardar la respuesta
            return new Directory([
                'user_id' => $this->userId,
                'client_id' => $client->id,
                'form_id' => $this->formId,
                'data' => json_encode($formAnswer)
            ]);
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function getRowCount()
    {
        return $this->rows_count;
    }

    /**
     * @author Jhon Bernal
     * Metodo que permite realizar tratamiento especial un dato especifico
     * @param $data_value
     * @param $key_value
     * @return mixed
     */
    public function dataFormat($data_value, $key_value,$formId)
    {
        //formatear a tipo fecha
        $data = trim($data_value);
        $fields = Section::where('form_id',$formId)->whereJsonContains('fields', ['controlType' => 'datepicker'])->first();
        if (isset($fields)) {
            $fields = json_decode($fields->fields);            
            foreach ($fields as $row) {
                if ($row->controlType == 'datepicker') {
                    if ($row->key == $key_value) {
                        $data = Carbon::parse($data_value)->toDateString();
                    }
                }               
            }
        }
        return $data;
    }

     /**
     * @author Jhon Bernal
     * Metodo que permite realizar contruccion de id fields section junto con base a la carga de excel
     * @param $row
     * @param $key_value
     * @param $client
     * @return mixed
     */
    public function buildDocumentExcel($keyValues, $row,$client)
    {
        // Se construye el formato del objeto de FormAnswer
        $temporal         = array(); // Array para hacer una lista de los keys del formulario
        $responseTemporal = []; // Array para llenar los key del formulario con los registros del excel
        foreach ($keyValues as $key) {
            foreach($key as $excelKey => $value){
                array_push($temporal, $excelKey);
            }
        }

        $count = count($temporal);
        $curso = array();
        for ($i = 0; $i < $count; $i++) {
            $curso[$temporal[$i]] = $row[$i];
        }
      
        array_push($responseTemporal, $curso);
        // Se organiza el regsitro de excel por secciones del formulario
        $i = 0;
        $j = 0;
        
        $arrayTemporal = array();
        foreach ($keyValues as $key) {
            foreach($key as $excelKey => $value){
                if(isset($responseTemporal[0][$excelKey])){
                    $arrayTemporal[$excelKey] = trim($responseTemporal[0][$excelKey]);
                    //renderiza e identifica id field para ser asignado a field_id
                    $fields = Section::where('form_id',$this->formId)->pluck('fields');
                    foreach ($fields as $field) {
                        $fieldArr = collect( json_decode($field))->where('key',$excelKey)->pluck('id');
                        if (!empty($fieldArr->all())) {
                            $fielId = $fieldArr->all();   
                        }                                   
                    }
                    $key_value = new KeyValue([
                        'form_id' => $this->formId,
                        'client_id' => $client->id,
                        'key' => $excelKey,
                        'value' => $this->dataFormat($responseTemporal[0][$excelKey],$excelKey,$this->formId),
                        'description' => null,
                        'field_id' =>  $fielId[0]
                    ]);
                    $key_value->save();
                }
                $j++;
            }
           
            array_push($this->sections, $arrayTemporal);
            $arrayTemporal= array();
            $i++;
        }
    }
}
