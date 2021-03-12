<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\FormExport;
use App\Imports\FormImport;
use Helpers\MiosHelper;

use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    /**
    * Olme Marin
    * 10-03-2021
    * Método para descargar la plantilla de excel del formularios
    */
    public function exportExcel(Request $request) {
        $json_body  = json_decode($request->getContent());
        $parameters = $json_body->parameters;
        $formExport = new FormExport();
        $headers    = $parameters;
        $formExport->headerMiosExcel($headers);
        return Excel::download(new FormExport,'plantilla.xlsx');
    }

     /**
    * Olme Marin
    * 10-03-2021
    * Método para importar info desde la plantilla de excel
    */
    public function importExcel(Request $request, MiosHelper $miosHelper) {
        $file = $request->file('excel');
        Excel::import(new FormImport, $file);
        $data = $miosHelper->jsonResponse(true, 200, 'message','Se realizó el cargue de forma exitosa');
        return response()->json($data, $data['code']);
    }
}
