<?php

namespace App\Exports;

use App\Models\Upload;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UploadsExport implements WithHeadings, WithMapping, FromQuery
{
    use Exportable;

    /**
     * @inheritDoc
     */
    public function setUploadId($id){
        $this->uploadId = $id;
        return $this;
    }

    public function query()
    {
        // TODO: Implement query() method.
        return Upload::where('id',$this->uploadId)->first();

    }

    public function headings(): array
    {
        // TODO: Implement headings() method.
        return [
            'Nombre archivo',
            'Fecha primer cargue',
            'Fecha actualización',
            'Total registros',
            'Cargados',
            'No cargados',
            'Errores'
        ];

    }

    public function map($row): array
    {
        $response = [
            $row->name,
            $row->created_at,
            $row->updated_at,
        ];
        // TODO: Implement map() method.
        if(!empty($row->resume)){
            $resumeObject = json_decode($row->resume);
            array_push($response,$resumeObject->totalRegistros);
            array_push($response,$resumeObject->cargados);
            array_push($response,$resumeObject->nocargados);
            if(count($resumeObject->errores) > 0){
                $errors = $this->getErrors($resumeObject->errores);
                Log::info('$errors :');
                Log::info($errors);
            }
        }

        return $response;
    }

    private function getErrors(array $arraySearch,array $arrayRecipe = []){
        Log::info(count(array_keys($arraySearch)));
        if(count(array_keys($arraySearch)) > 0){
            foreach ($arraySearch as $a){
                $this->getErrors($a,$arrayRecipe);
            }
        }else{
            return array_merge($arrayRecipe,$arraySearch);
        }
    }

}
