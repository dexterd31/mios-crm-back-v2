<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\FormExport;
use App\Imports\ClientImport;
use App\Imports\FormAnswerImport;
use Helpers\MiosHelper;
use App\Models\Upload;
use App\Models\Directory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FormReportExport;
use App\Services\CiuService;

class UploadController extends Controller
{

    private $ciuService;

    public function __construct(CiuService $ciuService)
    {
        $this->middleware('auth');
        $this->ciuService = $ciuService;
    }

    /**
     * Muestra todas las cargas de bases de datos organizadas por ultimas primero
     */
    public function index(Request $request, $form_id)
    {
        $menu= Upload::with('form:id,name_form')->where('form_id', $form_id)->orderBy('created_at', 'desc')->paginate($request->query('n', 5))->withQueryString();
        foreach ($menu as $value) {
            $user_info = $this->ciuService->fetchUserByRrhhId($value->user_id);
            $value->created_by = $user_info->rrhh->first_name.' '.$user_info->rrhh->last_name;
        }
        return $this->successResponse($menu);

    }

    /**
     * Olme Marin
     * 10-03-2021
     * Método para descargar la plantilla de excel del formularios
     */
    public function exportExcel($parameters)
    {
        $formExport = new FormExport();
        $headers    = utf8_encode(base64_decode($parameters));
        $formExport->headerMiosExcel(explode(",", $headers));
        return Excel::download(new FormExport, 'plantilla.xlsx');
    }

    /**
     * Olme Marin
     * 10-03-2021
     * Método para importar info desde la plantilla de excel
     */
    public function importExcel(Request $request, MiosHelper $miosHelper)
    {
        $file   = $request->file('excel');
        $userId = $request->user_id;
        $formId = $request->form_id;
        $flag = $request->flag;
        if (isset($file) && isset($userId) && isset($formId)) {
            
            //Eliminar registros de Directory
            if($flag != 'append'){
                Directory::where('form_id', $formId)->delete();
            }
            
            //Se guardan los clientes
            //try {
                Excel::import(new ClientImport, $file);
                //Se guarda en directory
                //try {
                    $form_import =new FormAnswerImport($userId, $formId);
                    Excel::import( $form_import, $file);

                    //Se agrega en la tabla de uploads
                    $upload             = new Upload();
                    $upload->name       = $file->getClientOriginalName();
                    $upload->user_id    = $userId;
                    $upload->form_id    = $formId;
                    $upload->count = $form_import->getRowCount();
                    $upload->method = empty($request->flag) ? 'replace': $request->flag;
                    $upload->save();
                    
                    $data = $miosHelper->jsonResponse(true, 200, 'message', 'Se realizó el cargue de forma exitosa');
                    return response()->json($data, $data['code']);
                /* } catch (\Throwable $th) {
                    $data = $miosHelper->jsonResponse(false, 400, 'message', 'Ha ocurrido un error al importar el archivo');
                    return response()->json($data, $data['code']);
                } */
            /* } catch (\Throwable $th) {
                $data = $miosHelper->jsonResponse(false, 400, 'message', 'Ha ocurrido un error al importar el archivo');
                return response()->json($data, $data['code']);
            } */
        } else {
            $data = $miosHelper->jsonResponse(false, 400, 'message', 'Faltan campos por ser diligenciados');
            return response()->json($data, $data['code']);
        }
    }

    public function exportDatabase(Request $request)
    {
      $headers    = $request->reportFields;
      $headers2 = [];

      $ids = [];
      $formAnswers_count = Directory::where('form_id',$request->formId)
                          ->where('created_at','>=', $request->date1)
                          ->where('created_at','<=', $request->date2)
                          ->select('data')->count();

      if($formAnswers_count==0){
          // 406 Not Acceptable
          // se envia este error ya que no esta mapeado en interceptor angular.
        return $this->errorResponse('No se encontraron datos en el rango de fecha suministrado', 406);
      } else if($formAnswers_count>1000){
        return $this->errorResponse('El rango de fechas supera a los 1000 records', 413);
      } else {

        $formAnswers = Directory::where('form_id',$request->formId)
                          ->where('created_at','>=', $request->date1)
                          ->where('created_at','<=', $request->date2)
                          ->select('data')->get();
        $i=0;

        $data = [];
        
        foreach($formAnswers as $answer){
          foreach(json_decode($answer->data) as $field){
            if(in_array($field->key, $headers)){
                $ids[$i][$field->key] = $field->value;
                if($i==0){
                  array_push($headers2, $field->key);
                }
              }
          }
          $i++;
        }
      }
      return Excel::download(new FormReportExport($ids, $headers2), 'base_de_datos.xlsx');
    }
}