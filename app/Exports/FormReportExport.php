<?php

namespace App\Exports;

use App\Models\FormAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class FormReportExport implements FromCollection, WithHeadings
{

  use Exportable;

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

      //dd($this->headers);

      // $formAnswers = DB::table('form_answers')->where('form_id',$this->form_id)
      //                     ->where('created_at','>=', $this->fecha_desde)
      //                     ->where('created_at','<=', $this->fecha_hasta)
      //                     ->select('structure_answer')
      //                     ->get();

      //                     return dd($formAnswers);


      $formAnswers = FormAnswer::where('form_id',$this->form_id)
                          ->where('created_at','>=', $this->fecha_desde)
                          ->where('created_at','<=', $this->fecha_hasta)
                          ->select('structure_answer')->get();

                        if(count($formAnswers)==0){
                          return 'Error al consultar los datos';
                        }else{
                          // return dd($formAnswers);
                          $i=0;

                          $data = [];
                          foreach($formAnswers as $answer){
                            foreach(json_decode($answer->structure_answer) as $structure){
                              foreach($structure as $id => $value){
                                $ids[$i][$id] = $value;
                                return dd();
                                if($ids[$i][$id] == $this->headers){
                                  array_push($data, $value);
                                }
                              }
                            }
                            $i++;
                          }


                          return dd($data);
                          // return dd($this->headers);
                          $idx=0;
                          foreach($this->headers as $data){
                            $datas[$idx] = $ids[$data];
                            $idx++;
                          }

                          return dd($datas);
                          return $datas;
                         // dd($value);
                        // dd($ids);

                        return array($ids);
                      }

                        //return [];




                       //  $formAnswer = array();
                       //  $keys = array();

                       // // dd($formAnswers);
                       //  foreach($formAnswers as $answer){

                       //     // dd(json_decode($answer->structure_answer));
                       //      foreach(json_decode($answer->structure_answer) as $section){
                       //         // dd(json_decode($answer->structure_answer));
                       //         foreach($section as $key => $value){
                       //              $keys[$key] = $value;
                       //          }
                       //          $formAnswer = $keys;
                       //         // $keys = array();
                       //      }
                       //  }
                       // //  dd($formAnswer);


                    // return (json_encode($formAnswer));
    }

    public function headings(): array
    {
        return $this->headers;
    }

    // public function headersExcel($headers){

    //     global $headersExcel;
    //     $headersExcel = $headers;
    //     return $headers;
    // }

}
