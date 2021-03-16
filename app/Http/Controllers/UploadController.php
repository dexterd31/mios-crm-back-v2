<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\FormExport;
use App\Imports\FormImport;
use App\Imports\KeyValuesImport;
use Helpers\MiosHelper;
use App\Models\Upload;
use App\Models\Directory;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    /**
    * Olme Marin
    * 10-03-2021
    * Método para descargar la plantilla de excel del formularios
    */
    public function exportExcel($parameters) {
        $formExport = new FormExport();
        $headers    = base64_decode($parameters);
        $formExport->headerMiosExcel(explode(",", $headers));
        return Excel::download(new FormExport,'plantilla.xlsx');
    }

    /**
    * Olme Marin
    * 10-03-2021
    * Método para importar info desde la plantilla de excel
    */
    public function importExcel(Request $request, MiosHelper $miosHelper) {
        $file   = $request->file('excel');
        $userId = $request->user_id;
        $formId = $request->form_id;
        if (isset($file) && isset($userId) && isset($formId)) {
            //Se agrega en la tabla de uploads
            $upload = new Upload();
            $upload->name       = $file->getClientOriginalName();
            $upload->user_id    = $userId;
            $upload->form_id    = $formId;
            $upload->save();
            //Eliminar registros de Directory
            Directory::where('form_id', $formId)->delete();
            //Se guardan los clientes
            Excel::import(new FormImport, $file);
            //Seguardan directory
            Excel::import(new KeyValuesImport($userId, $formId),$file);
            $data = $miosHelper->jsonResponse(true, 200, 'message','Se realizó el cargue de forma exitosa');
            return response()->json($data, $data['code']);
        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message', 'Faltan campos por ser diligenciados');
            return response()->json($data, $data['code']);
        }
    }
}
