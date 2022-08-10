<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

$headers2 = [];

class FormReportExport implements FromCollection, WithHeadings
{

  use Exportable;

    public $ids;
    public $headers;


    public function __construct($ids, $headers)
    {
        $this->ids = $ids;
        $this->headers = $headers;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
      return collect($this->ids);
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
