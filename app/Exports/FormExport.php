<?php

namespace App\Exports;

use App\Models\Form;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

$headersExcel = [];

class FormExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Form::all()->where('id', 0);
    }

    public function headings(): array
    {   
        global $headersExcel;
        return ['nombre', 'apellido'];
    }

    public function headerMiosExcel($headers){
        
        global $headersExcel;
        $headersExcel = $headers;
        return $headers;
    }
}
