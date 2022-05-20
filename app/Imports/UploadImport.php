<?php

namespace App\Imports;

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
        $this->fileInfo['rowsFile'] = -1;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        $rows->each(function ($row, $index) {
            if ($this->chunkCounter == 1 && !$index) {
                $row->each(function ($field) {
                    if (!is_null($field)) {
                        $this->fileInfo['columnsFile'][] = $field;
                    }
                });
            }

            $numOfColumns = count($this->fileInfo['columnsFile']);

            $row->each(function ($field, $index) use ($numOfColumns) {
                $countNullFields = 1;

                if (is_null($field)) {
                    $countNullFields++;
                }

                if ($countNullFields < $numOfColumns && ($index + 1) == $numOfColumns) {
                    $this->fileInfo['rowsFile']++;
                }
            });
        });

        $this->chunkCounter++;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getFileInfo()
    {
        return $this->fileInfo;
    }
}
