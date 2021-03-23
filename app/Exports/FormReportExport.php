<?php

namespace App\Exports;

use App\Models\FormAnswer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithHeadings;


class FormReportExport implements FromQuery, WithHeadings
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
     // dd($this->headers);
        $formAnswers = FormAnswer::where('form_id',$this->form_id)
                      //  ->whereBetween('created_at', [$this->fecha_desde, $this->fecha_hasta])
                          ->where('created_at','>=', $this->fecha_desde)
                          ->where('created_at','<=', $this->fecha_hasta)
                         // ->whereIn('key',$this->headers)
                        ->select('structure_answer')->get();

                        $formAnswer = array();
                        $keys = array();

                       // dd($formAnswers);
                        foreach($formAnswers as $answer){

                           // dd(json_decode($answer->structure_answer));
                            foreach(json_decode($answer->structure_answer) as $section){
                               // dd(json_decode($answer->structure_answer));
                               foreach($section as $key => $value){
                                    $keys[$key] = $value;
                                }
                                $formAnswer = $keys;
                               // $keys = array();
                            }
                        }
                       //  dd($formAnswer);
                        

                     return (json_encode($formAnswer));
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
