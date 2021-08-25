<?php

namespace App\Imports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ValidateImport implements ToCollection,
{

    public function collection(Collection $rows){
        return $rows;
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
