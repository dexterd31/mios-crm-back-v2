<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\FormExport;
use App\Imports\UploadImport;
use Helpers\MiosHelper;
use App\Models\Upload;
use App\Models\Directory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FormReportExport;
use App\Services\CiuService;
use Throwable;
use App\Imports\ClientNewImport;
use App\Http\Controllers\FormAnswerController;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

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
    * @param file ->excel - Archivo que va a ser cargado
    * @return array ->columnsFile Arreglo con el nombre de las columnas encontradas en el archivo
    * @return array ->prechargables Arreglo de objetos en el que se pasan el id y el label del field precargable [{"id": 11221212,"label": "Primer Nombre"},{"id": 7838473847,"label": "Primer Apellido"}].
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
                    $prechargables = $FormController->searchPrechargeFields($request->form_id)->getData();
                    \Log::info(json_encode($prechargables));
                    $answer['columnsFile'] = $form_import_validate[0][0];
                    $answer['prechargables']=[];
                    foreach($prechargables->section as $section){
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
            $data = $miosHelper->jsonResponse(false,500,"message",$e->getMessage());
            return response()->json($data, $data['code']);
        }
    }

    /**
     * @desc Función para cargar los clientes por medio de un excel
     * @param file ->excel Archivo que tiene los clientes que se cargara
     * @param integer ->form_id Id del formulario
     * @param array ->assigns Arreglo de Objetos con la assignación de idField a cada una de las columnas del campo [{"columnName":"Nombre","id":123456789890},{"columnName":"Apellido","id":12345678908787}]
     * @param string ->action Cadena de texto con dos posibles opciones update o none
     *
     */
    public function excelClients(Request $request , MiosHelper $miosHelper){
        //Primero Validamos que todos los parametros necesarios para el correcto funcionamiento esten
        $this->validate($request,[
            'excel' => 'required',
            'form_id' => 'required',
            'assigns' => 'required',
            'action' => 'required'
        ]);
        $userRrhhId=auth()->user()->rrhh_id;
        $file = $request->file('excel');
        $fileData = json_decode(Excel::toCollection(new ClientNewImport(), $file)[0]);
        if(count($fileData)>1){
            $formController = new FormController();
            $fieldsLoad=$formController->getSpecificFieldForSection(json_decode($request->assigns),$request->form_id);
            foreach(json_decode($request->assigns) as $assign){
                foreach($fieldsLoad as $key=>$field){
                    if($field->id == $assign->id){
                        $fieldsLoad[$assign->columnName]=$field;
                        unset($fieldsLoad[$key]);
                    }
                }
            }

            if(count($fieldsLoad)>0){
                $directories = [];
                $dataLoad=0;
                $dataNotLoad=[];
                foreach($fileData as $c=>$client){
                    $answerFields = (Object)[];
                    $errorAnswers = [];
                    $formAnswerClient=[];
                    $formAnswerClientIndexado=[];
                    foreach($client as $d=>$data){
                        $dataValidate=$this->validateClientDataUpload($fieldsLoad[$d],$data);
                        if($dataValidate->success){
                            foreach($dataValidate->in as $in){
                                if (!isset($answerFields->$in)){
                                    $answerFields->$in=[];
                                }
                                array_push($answerFields->$in,$dataValidate->$in);
                                array_push($directories,$dataValidate->$in);
                            }
                            array_push($formAnswerClient,$dataValidate->formAnswer);
                            array_push($formAnswerClientIndexado,$dataValidate->formAnswerIndex);
                        }else{
                            array_push($errorAnswers,$dataValidate['message']);
                        }

                    }
                    //array_push($dataToLoad,$answerFields);
                    if(count($errorAnswers)==0){
                        $clientController=new ClientNewController();
                        $newRequest = new Request();
                        $newRequest->replace([
                            "form_id" => $request->form_id,
                            "information_data" => json_encode($answerFields->informationClient),
                            "unique_indentificator" => json_encode($answerFields->uniqueIdentificator[0]),
                        ]);
                        $client=$clientController->create($newRequest);
                        if(isset($client->id)){
                            $formAnswerController=new FormAnswerController();
                            $formAnswerSave=$formAnswerController->create($client->id,$request->form_id,$formAnswerClient,$formAnswerClientIndexado,"upload");
                            if(isset($answerFields->preload)){
                                $keyValuesController= new KeyValueController();
                                $keyValues=$keyValuesController->createKeysValue($answerFields->preload,$request->form_id,$client->id);
                                if(!isset($keyValues->id)){
                                    array_push($errorAnswers,"No se han podido insertar keyValues para el cliente ".$client->id);
                                }
                            }
                            $dataLoad=$dataLoad+1;
                        }else{
                            array_push($errorAnswers,"No se han podido insertar el cliente ".$answerFields['uniqueIdentificator']['value']);
                        }
                    }else{
                        array_push($dataNotLoad,$errorAnswers);
                    }
                }
                $resume=["Total Registros: ".count($fileData) , "Cargados: ".$dataLoad, "No Cargados: ".count($dataNotLoad)];
                $data = $miosHelper->jsonResponse(true,200,"message",implode("<br>",$resume));
            }else{
                $data = $miosHelper->jsonResponse(false,400,"message","No se encuentra los campos en el formulario");
            }
        }else{
            $data = $miosHelper->jsonResponse(false,400,"message","El archivo que intenta cargar no tiene datos.");
        }
        return response()->json($data,$data['code']);
    }

    public function validateClientDataUpload($field,$data){
        $answer=new stdClass();
        $answer->success=false;
        $answer->message=[];
        /*$rules= isset($field->required) ? 'required' : '';
        $rules.= '|'.$this->kindOfValidationType($field->type);
        $rules.= isset($field->minLength) ? '|min:'.$field->minLength : '';
        $rules.= isset($field->maxLength) ? '|max:'.$field->maxLength : '';
        \Log::info($rules);
        $validator = Validator::make(['validation' => $data], [
            $field->label => $rules
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $message) {
                array_push($answer->message,$message." in ".$field->label);
            }
        }else{*/
            $field->value=$data;
            $answer->in=[];
            if(isset($field->isClientInfo)){
                $answer->informationClient=(object)[
                    "id" => $field->id,
                    "value" => $field->value
                ];
                array_push($answer->in,'informationClient');
            }
            if(isset($field->client_unique)){
                $answer->uniqueIdentificator = (Object)[
                    "id" => $field->id,
                    "key" => $field->key,
                    "preloaded" => $field->preloaded,
                    "label" => $field->label,
                    "isClientInfo" => $field->isClientInfo,
                    "client_unique" => $field->client_unique,
                    "value" => $field->value
                ];
                array_push($answer->in,'uniqueIdentificator');
            }
            if(isset($field->preloaded)){
                $answer->preload=[
                    "id" => $field->id,
                    "key" => $field->key,
                    "value" => $field->value
                ];
                array_push($answer->in,'preload');
            }
            \Log::info(json_encode($field));
            $answer->formAnswer = (Object)[
                "id" => $field->id,
                "key" => $field->key,
                "preloaded" => $field->preloaded,
                "label" => $field->label,
                "isClientInfo" => $field->isClientInfo,
                "client_unique" => isset($field->client_unique) ? $field->client_unique : false,
                "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
            ];
            $answer->formAnswerIndex = (Object)[
                "id" => $field->id,
                "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
            ];
            $answer->success=true;
            $answer->Originalfield=$field;
        //}
        return $answer;
    }

    private function kindOfValidationType($type){
        $answer='';
        switch($type){
            case "email":
                $answer="email";
            break;
            case "number":
                $answer="numeric";
            break;
            case "date":
                $answer="date";
            break;
            default:
                $answer="string";
            break;

        }
        return $answer;
    }

    /**
     * @desc Función para la generación del documento de gestion de clientes
     * @param Integer id de la gestión a consultar
     * @return File Archivo de excel con los datos de gestion
     */
    public function downloadManagement(Request $request){

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
