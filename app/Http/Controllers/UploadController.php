<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\FormExport;
use App\Imports\ClientImport;
use App\Imports\FormAnswerImport;
use App\Imports\UploadImport;
use Helpers\MiosHelper;
use App\Models\Upload;
use App\Models\Directory;
use App\Models\Section;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FormReportExport;
use App\Services\CiuService;
use Throwable;

class UploadController extends Controller
{

    private $ciuService;

    //Constante para limitar la carga de filas
    static $LIMIT_ROW_UPLOAD_FILE = 10000;

    //Constante para limitar la carga de filas
    static $LIMIT_CHARACTERS_CELL = 2000;

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
            $user_info = $this->ciuService->fetchUserByRrhhId($value->rrhh_id);
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
    * @desc Extrae la primera fila del archivo para ser mostrada al usaurio y realice el match de los datos cargados con los campos del formulario
    * @param file $excel - Archivo que va a ser cargado
    * @return array - Arreglo con el nombre de cada una de las columnas.
    * @author Leonardo Giraldo Quintero
    */
    public function extractColumnsNames(Request $request, MiosHelper $miosHelper){
        try {
            $file = $request->file('excel');
            $answer = [];
            if(isset($file)){
                $form_import_validate = Excel::toArray(new UploadImport, $file);
                if(count($form_import_validate[0])>1 && count($form_import_validate[0][0])>0 && $form_import_validate[0][0]<>NULL){
                    $FormController = new FormController();
                    $prechargables = json_decode($FormController->searchPrechargeFields($request->form_id));
                    $answer['columnsFile'] = $form_import_validate[0][0];
                    $answer['prechargables']=[];

                    foreach($prechargables->original->section as $section){
                        foreach($section->fields as $field){
                            if($field){
                                $prechargedField=new \stdClass();
                                $prechargedField->id=$field->id;
                                $prechargedField->label=$field->label;
                                array_push($answer['prechargables'],$prechargedField);
                            }

                        }
                    }

                    $data = $miosHelper->jsonResponse(true,200,"data",$answer);
                }else{
                    $data = $miosHelper->jsonResponse(false,406,"message","El archivo cargado no tiene datos para cargar, recuerde que en la primera fila se debe utilizar para identificar los datos asignados a cada columna.");
                }
            }else{
                $data = $miosHelper->jsonResponse(false,406,"message","No se encuentra ningun archivo");
            }
            return response()->json($data, $data['code']);
        } catch (Throwable $e) {
            $data = $miosHelper->jsonResponse(false,400,"message",$e->getMessage());
            return response()->json($data, $data['code']);
        }
    }

    /**
     * @desc Función para cargar los clientes por medio de un excel
     * @param file $excel Archivo que tiene los clientes que se cargara
     */
    public function excelClients(Request $request , MiosHelper $miosHelper){
        //Primero Validamos que todos los parametros necesarios para el correcto funcionamiento esten
        $this->validate($request,[
            'excel' => 'required',
            'user_id' => 'required',
            'form_id' => 'required',
            'assigns' => ['required','json'],
            'action' => 'required'
        ]);
        $file = $request->file('excel');
        $form_import_validate = Excel::import(new ClientImport(json_decode($request->assigns),$request->form_id), $file);

    }




    /**
     * Olme Marin
     * 10-03-2021
     * Método para importar info desde la plantilla de excel
     */
    public function importExcel(Request $request, MiosHelper $miosHelper)
    {
        $file   = $request->file('excel');
        $userAuth = auth()->user();
        $rrhhId = $userAuth->rrhh_id;
        $formId = $request->form_id;
        $flag = $request->flag;
        if (isset($file) && isset($rrhhId) && isset($formId)) {
            //Eliminar registros de Directory
            if($flag != 'append'){
                Directory::where('form_id', $formId)->delete();
            }

            /*start -- validacion documento cargado--*/
            $form_import_validate = Excel::toCollection(new ValidateImport, $file);
            $countDocumentLoad = count($form_import_validate[0]);
            $documentLoad = $form_import_validate[0];
            $errorResponse = [];

            // Determina cantidad de columnas a cargar documento
            if ($countDocumentLoad > self::$LIMIT_ROW_UPLOAD_FILE) {
               $errorResponse[] = 'Limite de registros no permitidos.';
            }

            // Determina si tiene valores el documento
            if ($countDocumentLoad > 1) {
                 foreach ($documentLoad as $keyRows => $rows) {

                    //entrada solo datos, cabecera
                    if ($keyRows != 0) {
                        //Determina si la columna Tipo de documento sea de tipo entero para relacionar
                        if (is_string($rows[4])) {
                           $errorResponse[] = 'La fila '.($keyRows + 1).' debe ser id tipo numerico para la columna tipo de documento';
                        }
                        //Determina si tiene un id para relacionar
                        if (is_null($rows[4])) {
                           $errorResponse[] = 'La fila '.($keyRows + 1).' no cuenta con  id tipo numerico para la columna tipo de documento';
                        }
                    }
                    //Determina que cada fila tenga la cantidad de celdas en base a la cabecera
                    $filteredHead = $documentLoad[0]->filter(function ($value, $key) {
                        return $value != null;
                    });
                    $rowsCount = $rows->filter(function ($value, $key) {
                        return $value != null;
                    });
                    if (count($rowsCount) > count($filteredHead)) {
                       $errorResponse[] = 'La fila ' . ($keyRows + 1) .' supera la cantidad de celdas con información';
                    }
                    foreach ($rows as $keyRowsCell => $valueRowsCell) {
                        //Determina cantidad de caracteres de cada celda
                        if (strlen($valueRowsCell) > self::$LIMIT_CHARACTERS_CELL) {
                           $errorResponse[] = 'La fila '.$keyRows.' de la celda '.$keyRowsCell.' cuenta con mas de 2000 caracteres permitidos.';
                        }
                    }
                }
            }
            if ($errorResponse != []) {
                $data = $miosHelper->jsonResponse(true,420, 'message', 'Se han encontrado los siguinetes errores al cargar el archivo: '.implode('<br>',$errorResponse));
                return response()->json($data, $data['code']);
            }
            /*end--validacion documento cargado--*/

            //Se guardan los clientes
            //try {
                Excel::import(new ClientImport, $file);
                //Se guarda en directory
                //try {
                    $form_import =new FormAnswerImport($rrhhId, $formId, json_decode($request->ids));
                    Excel::import($form_import, $file);
                    //dd('Row count: ' . $form_import->getRowCount());


                    //Se agrega en la tabla de uploads
                    $upload             = new Upload();
                    $upload->name       = $file->getClientOriginalName();
                    $upload->rrhh_id    = $rrhhId;
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
