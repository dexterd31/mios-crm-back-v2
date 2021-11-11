<?php

namespace App\Imports;

use App\Http\Controllers\UploadController;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;


class ClientNewImport implements ToCollection, WithHeadingRow
{

    public function __construct()
    {
        HeadingRowFormatter::default('none');
    }

    public function collection(Collection $collection)
    {
        return $collection;
    }
}
