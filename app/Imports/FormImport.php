<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\KeyValue;
use Maatwebsite\Excel\Concerns\ToModel;


class FormImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if (is_numeric($row[4]) || $row[4] == null) {
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
            }
        }
    }
}
