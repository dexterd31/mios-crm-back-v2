<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Circuits;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;

class CircuitsImport implements ToModel


{
    /**
     * @param array $row
     *
     * @return Circuits|null
     */
    public function model(array $row)
    {
        return new Circuits([
           'name'     => $row[0],
        ]);
    }
}
