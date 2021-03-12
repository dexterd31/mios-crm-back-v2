<?php

namespace App\Imports;

use App\KeyValue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMappedCells;

$dataExcel = [];

class KeyValuesImport implements WithMappedCells, ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
 
    public function mapping(): array
    {
        global $dataExcel;
        //$count = count($dataExcel);
        var_dump($dataExcel);
        
        return [
            'key'   => 'I1',
            'value' => 'J1',
        ];
    }

    public function olme($data) {
        var_dump('esto es olme');
        var_dump($data);
        global $dataExcel;
        $dataExcel = $data;
    }

    public function model(array $row)
    {
        global $dataExcel;
        $dataExcel = $row;
        $keyValuesImport = new KeyValuesImport();
        $keyValuesImport->olme($row);
      
        die();

        return new KeyValue([
            //
        ]);
    }
}
