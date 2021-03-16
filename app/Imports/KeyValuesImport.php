<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Directory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class KeyValuesImport implements ToModel, WithBatchInserts
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
        if ($this->num == 0) {
            $this->headers = $row;
            $this->num = $this->num + 1;
        } else {
            $this->sections = [];
            // Obtener el id del cliente
            $client = Client::where('document', $row[5])->select('id')->first();

            // Se crea el obajecto de sections
            $count = count($row);
            $curso = array();
            for ($i = 8; $i < $count; $i++) {

                $curso[$this->headers[$i]] = $row[$i];
                // $register = [
                //     '' . $this->headers[$i] . '' => $row[$i]
                // ];

                //array_push($this->sections, $register);
            }
            array_push($this->sections, $curso);
            // Se crea el objecto para guardar la respuesta
            return new Directory([
                'user_id' => $this->userId,
                'client_id' => $client->id,
                'form_id' => $this->formId,
                'data' => json_encode($this->sections)
            ]);
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
