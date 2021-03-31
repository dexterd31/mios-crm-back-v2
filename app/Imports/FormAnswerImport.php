<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Directory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
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
    public $num = 0;
    public $sections = [];

    public function __construct($userId, $formId)
    {
        $this->userId = $userId;
        $this->formId = $formId;
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

            // Se pasan los labels para obtener los keyvalues del formulario
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
                $curso[$temporal[$i]] = $row[$i];
            }
            array_push($responseTemporal, $curso);
            
            // Se organiza el regsitro de excel por secciones del formulario
            $i = 0;
            $arrayTemporal = array();
            foreach ($keyValues as $key) {
                foreach($key as $excelKey => $value){
                    $arrayTemporal[$excelKey] = trim($responseTemporal[0][$excelKey]);
                }
                array_push($this->sections, $arrayTemporal);
                $arrayTemporal= array();
                $i++;
            }

            // Se normaliza el objeto de FormAnswer
            $formAnswer = $formAnswerHelper->structureAnswer($this->formId, $this->sections);

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
}