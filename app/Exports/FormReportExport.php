<?php

namespace App\Exports;

use App\Models\KeyValue;
use Maatwebsite\Excel\Concerns\FromCollection;

class FormReportExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $form_id = 0;
        return KeyValue::where('form_id',$form_id)
        ->select('key','value')->get();
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
