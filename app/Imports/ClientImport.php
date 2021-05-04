<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\KeyValue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;


class ClientImport implements ToModel, WithBatchInserts
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
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
