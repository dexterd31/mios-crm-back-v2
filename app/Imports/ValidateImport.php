<?php

namespace App\Imports;

use App\Models\Directory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class ValidateImport implements ToModel, WithBatchInserts
{
    
    private $rows = 0;

    public function model(array $row)
    {
        ++$this->rows;
    
        return $this->saveDirectory();
    }

    public function batchSize(): int
    {
        return 1000;
    }
    
    public function getRowCount(): int
    {
        return $this->rows;
    }


    public function saveDirectory(){
        if ($this->getRowCount() < 0) {
            $directory =  new Directory();
            $directory->rrhh_id = 1;
            $directory->client_id = 1;
            $directory->form_id = 1;
            $directory->data = json_encode([]);    
            return $directory;
        }       
    }
}
