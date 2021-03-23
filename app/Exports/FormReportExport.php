<?php

namespace App\Exports;

use App\Models\KeyValue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Http\Request;


class FormReportExport implements FromCollection
{
    public $fecha_desde;
    public $fecha_hasta;
    public $form_id;
    public $headers;

    public function __construct($form_id, $fecha_desde, $fecha_hasta,$headers)
    {
        $this->fecha_desde = $fecha_desde;
        $this->fecha_hasta = $fecha_hasta;
        $this->form_id = $form_id;
        $this->headers = explode(",", $headers);
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
      
        $keyvalue = KeyValue::where('form_id',$this->form_id)
                        ->whereBetween('key_values.created_at', [$this->fecha_desde, $this->fecha_hasta])
                        ->select('key','value')->get();

                    
                    //    dd($keyvalue);
    }

    public function headings(): array
    {   
        return $this->headers;
    }

    public function headersExcel($headers){
        
        global $headersExcel;
        $headersExcel = $headers;
        return $headers;
    }

}
