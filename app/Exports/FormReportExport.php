<?php

namespace App\Exports;

use App\Models\KeyValue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Http\Request;


class FormReportExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
      
        return KeyValue::all();
    }

    public function headings(): array
    {   
        global $headersExcel;
        return $headersExcel;
    }

    public function headersExcel($headers){
        
        global $headersExcel;
        $headersExcel = $headers;
        return $headers;
    }
}
