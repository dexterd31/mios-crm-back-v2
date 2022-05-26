<?php

namespace App\Imports;

use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UploadImport implements ToCollection, WithChunkReading
{
    private $fileInfo = [];
    private $chunkCounter = 1;

    public function __construct()
    {
        $this->fileInfo['columnsFile'] = [];
        $this->fileInfo['rowsFile'] = 0;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            if ($this->chunkCounter == 1 && !$index) {
                foreach ($row as $field) {
                    if (!is_null($field)) {
                        $this->fileInfo['columnsFile'][] = $field;
                    } else {
                        throw new Exception("Por favor verifique que la cabecera de las columnas no esten vacias.");
                        break;
                    }
                }
            } else {
                $this->fileInfo['rowsFile']++;
            }
        }

        $this->chunkCounter++;
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    public function getFileInfo()
    {
        return $this->fileInfo;
    }
}
