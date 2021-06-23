<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Directory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use App\Models\KeyValue;
use Helpers\FormAnswerHelper;

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
            $keyValues = $formAnswerHelper->getKeysValuesForExcel($this->headers, $this->formId);
            // Se construye el formato del objeto de FormAnswer
            $temporal         = array(); // Array para hacer una lista de los keys del formulario
            $responseTemporal = []; // Array para llenar los key del formulario con los registros del excel
            foreach ($keyValues as $key) {
                foreach($key as $excelKey => $value){
                    array_push($temporal, $excelKey);
                }
            }
            $count = count($row);
            $curso = array();
            for ($i = 0; $i < $count; $i++) {
                /*if($row[$i] === null){
                    break;
                } else {*/
                    if(strlen($row[$i])<256){
                        $curso[$temporal[$i]] = $row[$i];
                    }
                //}
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

                        $key_value = new KeyValue([
                            'form_id' => $this->formId,
                            'client_id' => $client->id,
                            'key' => $excelKey,
                            'value' => trim($responseTemporal[0][$excelKey]),
                            'description' => null,
                            'field_id' => $this->ids[$j]
                        ]);
                        $key_value->save();
                    }
                    $j++;
                }
                array_push($this->sections, $arrayTemporal);
                $arrayTemporal= array();
                $i++;
            }

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
}
