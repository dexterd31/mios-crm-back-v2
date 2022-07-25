<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Exports\FormExport;
use App\Imports\UploadImport;
use Helpers\MiosHelper;
use App\Models\Upload;
use App\Models\Directory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FormReportExport;
use App\Services\CiuService;
use App\Imports\ClientNewImport;
use App\Managers\ClientsManager;
use App\Models\Channel;
use App\Models\CustomField;
use App\Models\Form;
use App\Models\FormAnswer;
use App\Models\ImportedFile;
use App\Models\Tag;
use App\Models\FormAnswerLog;
use App\Traits\FieldsForSection;
use App\Traits\FindAndFormatValues;
use stdClass;
use Throwable;

class UploadController extends Controller
{
    use FieldsForSection, FindAndFormatValues;

    //Constante para limitar la carga de filas
    static $LIMIT_ROW_UPLOAD_FILE = 10000;

    //Constante para limitar la carga de filas
    static $LIMIT_CHARACTERS_CELL = 2000;

    protected $formController;
    private $ciuService;

    public function __construct()
    {
        ini_set('max_execution_time', 300);
        $this->middleware('auth', ['except' => 'uploadClientDataFromEmail']);
    }

    /**
     * Muestra todas las cargas de bases de datos organizadas por ultimas primero
     */
    public function index(Request $request, $form_id)
    {
        $menu= Upload::with('form:id,name_form')->where('form_id', $form_id)->orderBy('created_at', 'desc')->paginate($request->query('n', 5))->withQueryString();
        foreach ($menu as $value) {
            $ciuService = new CiuService();
            $user_info = $ciuService->fetchUserByRrhhId($value->rrhh_id);
            $value->created_by = $user_info->rrhh->first_name.' '.$user_info->rrhh->last_name;
        }
        return $this->successResponse($menu);

    }

    /**
     * Olme Marin
     * 10-03-2021
     * Método para descargar la plantilla de excel del formularios
     * @param $parameters:
     * @return array: retorna un arreglo con las siguientes claves:
     *                - columnsFile: arreglo con los nombres de las columnas
     *                - prechargables: arreglo con los datos precargables del formulario
     *                - rowsFile: número de filas cargadas
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
    public function extractColumnsNames(Request $request, MiosHelper $miosHelper)
    {
        $this->validate($request, [
            'excel' => 'required|file',
            'form_id' => 'required|exists:forms,id'
        ]);

        try {
            $uploadImport = new UploadImport;
            $file = $request->file('excel');
            Excel::import($uploadImport, $file);
            $fileInfo = $uploadImport->getFileInfo();

            if(count($fileInfo['columnsFile']) && $fileInfo['rowsFile'] > 0){
                $FormController = new FormController();
                $prechargables = $FormController->searchPrechargeFields($request->form_id)->getData();
                $fileInfo['prechargables'] = [];

                foreach($prechargables->section as $section){
                    foreach($section->fields as $field){
                        if($field){
                            $prechargedField = new stdClass();
                            $prechargedField->id = $field->id;
                            $prechargedField->label = $field->label;
                            array_push($fileInfo['prechargables'], $prechargedField);
                        }
                    }
                }

                $data = $miosHelper->jsonResponse(true,200,"data",$fileInfo);
                $data['tags'] = Tag::formFilter($request->form_id)->get(['id', 'name']);
                $customFields = CustomField::formFilter($request->form_id)->first();
                $data['custom_fields'] = $customFields ? $customFields->fields : [];
            }else{
                $data = $miosHelper->jsonResponse(false,406,"message","El archivo cargado no tiene datos para cargar, recuerde que en la primera fila se debe utilizar para identificar los datos asignados a cada columna.");
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
     */
    public function excelClients(Request $request , MiosHelper $miosHelper)
    {
        $this->validate($request, [
            'excel' => 'required',
            'form_id' => 'required',
            'assigns' => 'required',
            'action' => 'required',
            'assign_users' => 'required',
            'rows_file' => 'required'
        ]);

        // Creacion de los tags
        $tags = json_decode($request->tags);
        $tagsIds = [];
        if (count($tags)) {
            foreach ($tags as $tag) {
                if ($tag->id) {
                    $tagsIds[] = $tag->id;
                } else {
                    $tag = Tag::create([
                        'name' => $tag->name,
                        'form_id' => $request->form_id
                    ]);
                    $tagsIds[] = $tag->id;
                }
            }
        } else {
            $tag = Tag::create([
                'name' => Carbon::now('America/Bogota')->toDateTimeString(),
                'form_id' => $request->form_id
            ]);
            $tagsIds[] = $tag->id;
        }

        $assignUsers = filter_var($request->assign_users,FILTER_VALIDATE_BOOLEAN);

        if($assignUsers){
            $assignUsersObject = $this->getAdvisers($request, $request->rows_file);
        }

        $userRrhhId = auth()->user()->rrhh_id;
        $file = $request->file('excel');

        $importedFile = ImportedFile::create(['name' => $file->getClientOriginalName()]);

        // Creacion de los campos personalizados
        $customFieldsIds = [];

        $custom_fields = json_decode($request->custom_fields);
        if (count($custom_fields)) {
            foreach ($custom_fields as $key => $field) {
                $customFieldsIds[] = $field->id;
                //Reemplaza todos los acentos o tildes de la cadena
                $fieldKey = $miosHelper->replaceAccents($field->label);
                //Reemplaza todos los caracteres extraños
                $fieldKey = preg_replace('([^A-Za-z0-9 ])', '', $fieldKey);
                //Convertimos a minusculas y Remplazamos espacios por el simbolo -
                $fieldKey = strtolower( str_replace(array(' ', '  '), '-', $fieldKey));
                //Concatenamos el resultado del label transformado con la variable $cadena
                $custom_fields[$key]->key = "$fieldKey-$field->id";
            }
            
            $customField = CustomField::formFilter($request->form_id)->first();

            if ($customField) {
                $fields = $customField->fields;
                foreach ($custom_fields as $field) {
                    $found = false;
                    foreach ($fields as $existingField) {
                        if ($existingField->id == $field->id) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $fields[] = $field;
                    }
                }
                $customField->fields = $fields;
                $customField->save();
            } else {
                CustomField::create([
                    'form_id' => $request->form_id,
                    'fields' => $custom_fields
                ]);
            }
        }

        $customFields = [];
        $fieldsLoad = $this->getSpecificFieldForSection(json_decode($request->assigns), $request->form_id);
        foreach (json_decode($request->assigns) as $assign) {
            $found = false;
            foreach ($fieldsLoad as $key => $field) {
                if ($field->id == $assign->id) {
                    $found = true;
                    $fieldsLoad[$assign->columnName] = $field;
                    unset($fieldsLoad[$key]);
                    break;
                }
            }
            if (!$found) {
                $customFields[$assign->columnName] = $assign->id;
            }
            if (count($customFieldsIds)) {
                if (in_array($assign->id, $customFieldsIds)) {
                    $customFields[$assign->columnName] = $assign->id;
                }
            }
        }

        if (count($fieldsLoad)) {
            $clientNewImport = new ClientNewImport($this, $request->form_id, filter_var($request->action, FILTER_VALIDATE_BOOLEAN), $fieldsLoad, $assignUsersObject ?? null, $tagsIds, $customFields, $importedFile->id);
            Excel::import($clientNewImport, $file);

            $resume = $clientNewImport->getResume();
            $informe = new stdClass();
            $informe->totalArchivo = $resume->totalRegistros;
            $informe->cargados = $resume->cargados;
            $informe->noCargados = $resume->nocargados;
            $informe->resumenHtml = implode("<br>",["<b>Total Archivo</b>: $resume->totalRegistros " , "<b>Cargados</b>: $resume->cargados", "<b>No Cargados</b>: $resume->nocargados"]);
            $saveUploadRequest = new Request();
            $saveUploadRequest->replace([
                "name" => $file->getClientOriginalName(),
                "rrhh_id" => $userRrhhId,
                "form_id" => $request->form_id,
                "count" => $resume->cargados,
                "method" => $request->action,
                "resume"=> json_encode($resume)
            ]);
            $uploadId = $this->saveUpload($saveUploadRequest);
            $response = new stdClass();
            $response->uploadId = $uploadId;
            $response->informe = $informe;
            $data = $miosHelper->jsonResponse(true,200,"data",$response);
        } else {
            $data = $miosHelper->jsonResponse(false,400,"message","No se encuentra los campos en el formulario");
        }

        return response()->json($data,$data['code']);
    }


    /**
     * ETL
     *
     */
    public function excelClientsETL(Request $request , MiosHelper $miosHelper, FormController $formController, ClientNewController $clientNewController, FormAnswerController $formAnswerController, KeyValueController $keyValuesController,GroupController $groupController,RelAdvisorClientNewController $relAdvisorClientNewController){
        //Primero Validamos que todos los parametros necesarios para el correcto funcionamiento esten
        $this->validate($request,[
            'excel' => 'required',
            'form_id' => 'required',
            'assigns' => 'required',
            'action' => 'required',
            'assignUsers' => 'required',
        ]);
        $file = $request->file('excel');
        $fileData = json_decode(Excel::toCollection(new ClientNewImport(), $file)[0]);
        $totalArchivos = count($fileData);
        $clientsNewExcel=[];
        $formAnswersExcel=[];
        $keysValueExcel=[];
        $errorAnswers=[];
        if($totalArchivos>0){
            $fieldsLoad = $this->getSpecificFieldForSection(json_decode($request->assigns),$request->form_id);
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
                    $idRef=$c+1;
                    $answerFields = (Object)[];
                    $errorAnswers = [];
                    $formAnswerClient=[];
                    $formAnswerClientIndexado=[];
                    $updateExisting = true;
                    foreach($client as $d=>$data){
                        $dataValidate=$this->validateClientDataUpload($fieldsLoad[$d],$data,$request->form_id);
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
                            $fila = strval(intval($c) + 1);
                            $columnErrorMessage = "Error en la Fila $fila";
                            array_push($dataValidate->message,$columnErrorMessage);
                            array_push($errorAnswers,$dataValidate->message);
                        }
                    }
                    //array_push($dataToLoad,$answerFields);
                    if(count($errorAnswers)==0){
                            $client=[];
                            $client["llave"] = $idRef;
                            $client["form_id"] = $request->form_id;
                            $client["information_data"] = json_encode($answerFields->informationClient);
                            $client["unique_indentificator"] = json_encode($answerFields->uniqueIdentificator[0]);
                            $client['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                            $client['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                            array_push($clientsNewExcel,$client);

                            $formAnswer=[];
                            $formAnswer["structure_answer"] = $formAnswerClient;
                            $formAnswer["created_at"] = Carbon::now()->format('Y-m-d H:i:s');
                            $formAnswer["updated_at"] = Carbon::now()->format('Y-m-d H:i:s');
                            $formAnswer["form_id"] = $request->form_id;
                            $formAnswer["chanel_id"] = 1;
                            $formAnswer["client_id"] = '';
                            $formAnswer["rrhh_id"] = 1;
                            $formAnswer["client_new_id"] = $idRef;
                            $formAnswer["form_answer_index_data"] = $formAnswerClientIndexado;
                            $formAnswer["tipification_time"] = "upload";
                            array_push($formAnswersExcel,$formAnswer);

                            foreach ($answerFields->preload as $keyValueData){
                                if(isset($keyValueData["value"])){
                                    if(is_array($keyValueData["value"])){
                                        $keyValueData["value"] = implode(",",$keyValueData["value"]);
                                    }
                                    $keyValue = [];
                                    $keyValue['key'] = $keyValueData["key"];
                                    $keyValue['value'] = $keyValueData["value"];
                                    $keyValue['description'] = 'etl';
                                    $keyValue['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                    $keyValue['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                    $keyValue['client_id'] = '';
                                    $keyValue['form_id'] = $request->form_id;
                                    $keyValue['field_id'] = $keyValueData["id"];
                                    $keyValue['client_new_id'] = $idRef;
                                    array_push($keysValueExcel, $keyValue);
                                }
                            }
                        }
                }
                Excel::store(new FormReportExport($clientsNewExcel, ["Llave","form_id","information_data","unique_indentificator","created_at","updated_at"]), 'ClientNews'.$request->form_id.'.xlsx');
                Excel::store(new FormReportExport($formAnswersExcel, ["structure_answer","created_at","updated_at","form_id","chanel_id","client_id","rrhh_id","client_new_id","form_answer_index_data","tipification_time"]), 'FormAnswers'.$request->form_id.'.xlsx');
                Excel::store(new FormReportExport($keysValueExcel, ['key','value','description','created_at','updated_at','client_id','form_id','field_id','7client_new_id']), 'KeyValues'.$request->form_id.'.xlsx');
                Excel::store(new FormReportExport($errorAnswers, ['error']), 'Errores'.$request->form_id.'.xlsx');
                $data = $miosHelper->jsonResponse(true,200,"data","Ok");
            }else{
                $data = $miosHelper->jsonResponse(false,400,"message","No se encuentra los campos en el formulario");
            }
        }else{
            $data = $miosHelper->jsonResponse(false,400,"message","El archivo que intenta cargar no tiene datos.");
        }
        return response()->json($data,$data['code']);
    }




    public function validateClientDataUpload($field,$data,$formId = null){
        $answer=new stdClass();
        $answer->success=false;
        $answer->message=[];
        if($formId != null){
            $formatValue = $this->findAndFormatValues($formId,$field->id,$data);
            if($formatValue->valid){
                $data = $formatValue->value;
            }else{
                array_push($answer->message,$formatValue->message." in ".$field->label);
                return $answer;
            }
        }
        $rules = '';
        $validationType = $this->kindOfValidationType($field->type,$data);
        if(isset($field->required) && $field->required){
            $rules = 'required';
            if(isset($field->minLength) && isset($field->maxLength)){
                $minLength = $field->minLength;
                $maxLength = $field->maxLength;
                if($field->type == 'number'){
                    $minLen = "0";
                    $maxLen = "";
                    $minLen .= str_repeat("0", intval($field->minLength) - 1);
                    $maxLen .= str_repeat("9", intval($field->maxLength));
                    $minLength = $minLen;
                    $maxLength = $maxLen;
                }
                $rules.= '|min:'.$minLength;
                $rules.= '|max:'.$maxLength;
            }
            $rules.= '|'.$validationType->type;
        }
        $fieldValidator = str_replace(['.','-','*',','],'',$field->label);
        $validator = Validator::make([$fieldValidator=>$validationType->formatedData], [
            $fieldValidator => $rules
        ]);
        if ($validator->fails()){
            foreach ($validator->errors()->all() as $message) {
                array_push($answer->message,$message." in ".$field->label);
            }
        }else{
            $field->value=$validationType->formatedData;
            $answer->in=[];
            if(isset($field->isClientInfo) && $field->isClientInfo){
                $answer->informationClient= (object)[
                    "id" => $field->id,
                    "value" => $field->value
                ];
                array_push($answer->in,'informationClient');
            }
            if(isset($field->client_unique) && $field->client_unique){
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
            if(isset($field->preloaded) && $field->preloaded){
                $answer->preload=[
                    "id" => $field->id,
                    "key" => $field->key,
                    "value" => $field->value
                ];
                array_push($answer->in,'preload');
            }
            $answer->formAnswer = (Object)[
                "id" => $field->id,
                "key" => $field->key,
                "preloaded" => $field->preloaded,
                "label" => $field->label,
                "isClientInfo" => $field->isClientInfo,
                "client_unique" => isset($field->client_unique) ? $field->client_unique : false,
                "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value,
                "controlType" => $field->controlType,
                "type" => $field->type
            ];
            $answer->formAnswerIndex = (Object)[
                "id" => $field->id,
                "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
            ];
            $answer->success=true;
            $answer->Originalfield=$field;
        }
        return $answer;
    }

    /**
     * @desc Función para la generación del documento de gestion de clientes
     * @param Integer id de la gestión a consultar
     * @return File Archivo de excel con los datos de gestion
     */
    public function downloadManagement(Request $request){
        $this->validate($request,[
            'uploadId' => 'required',
        ]);
        $upload = Upload::where('id',$request->uploadId)->first();
        $objectUpload = json_decode($upload);
        $resumen = json_decode($objectUpload->resume);
        $listaErrores = [];
        foreach ($resumen->errores as $errores){
                foreach($errores as $erroresFila){
                    foreach($erroresFila as $error){
                        array_push($listaErrores,$error);
                    }
                }
        }
        $response = [
            "Nombre archivo: $objectUpload->name ".PHP_EOL,
            "Fecha de carga:  ".Carbon::parse($objectUpload->created_at)->timezone('America/Bogota')->format('Y-m-d')." ".PHP_EOL,
            "total registros: $resumen->totalRegistros ".PHP_EOL,
            "archivos cargados: {$resumen->cargados} ".PHP_EOL,
            "archivos no cargados: {$resumen->nocargados} ".PHP_EOL,
            'Errores: '.PHP_EOL,
            implode(PHP_EOL,$listaErrores)
        ];
        File::put('../storage/app/temp.txt',$response);
        return response()->download('../storage/app/temp.txt','temp.txt',[
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-store, no-cache',
        ]);
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

    /**
     * @desc Almacena datos en la tabla upload
     * @param Request $request
     * @return bool
     */
    public function saveUpload(Request $request){
        $uploadModel = new Upload();
        $uploadModel->name = $request['name'];
        $uploadModel->rrhh_id = $request['rrhh_id'];
        $uploadModel->form_id = $request['form_id'];
        $uploadModel->count = $request['count'];
        $uploadModel->method = $request['method'];
        $uploadModel->resume = $request['resume'];
        $uploadModel->save();
        return DB::getPdo()->lastInsertId();
    }

    /**
     * @desc retorna los asesores junto con las cantidades que se deben cargar de cada uno
     * @param Request $request
     * @param int $totalArchivos
     * @return stdClass : objeto que contiene los datos de los asesores
     */
    private function getAdvisers(Request $request, int $totalArchivos):stdClass{
        $groupController = new GroupController();
        $formController = new FormController();
        $response = new stdClass();

        //se obtiene el group_id
        $groupId =  json_decode($formController->searchForm($request->form_id)->getContent())->group_id;
        $advisers = $groupController->searchGroup($groupId)['members'];
        $advisersCount = count($advisers);
        if(!$advisersCount){
            return $this->errorResponse("No se encuentran asesores para asignar los registros",400);
        }
        //se valida si el resultado es exacto
        $quantity = $totalArchivos / $advisersCount;
        if(is_float($quantity)){
            //se aproxima el valor para repartirlo proporcionalmente
            // $equalRegisters = round($quantity,0,PHP_ROUND_HALF_DOWN);
            $equalRegisters = (int) floor($quantity);
            // se le asigna a cada asesor la cantidad correspondiente de datos
            $advisers = array_map(function (&$item) use ($equalRegisters) {
                $item['quantity'] = $equalRegisters;
                return $item;
            }, $advisers);
            // se extrae el residuo de la división para asignarlo uno a uno entre los asesores hasta que quede en 0
            $moduleRegisters = $totalArchivos % count($advisers);
            $advisers = array_map(function (&$item) use (&$moduleRegisters) {
                if($moduleRegisters > 0){
                    $item['quantity']++;
                    $moduleRegisters--;
                }
                return $item;
            }, $advisers);
        }else{
            // se le asigna a cada asesor la cantidad correspondiente de datos
            $advisers = array_map(function (&$item) use ($quantity) {
                $item['quantity'] = $quantity;
                return $item;
            }, $advisers);
        }
        //se generan las banderas para contar la cantidad y saber el indice del asesor
        $response->quantity = 1;
        $response->advisersIndex = 0;
        $response->advisers = $advisers;
        return $response;
    }

    /**
     * crea las validaciones correspondientes a cada tipo de dato
     * @param $type
     * @param $data
     * @return object objeto con 2 valores: 1. type: tipo de validación, 2. formatedData: dato formateado según lo requiera el campo
     */
    private function kindOfValidationType($type,$data){
        $answer = new stdClass();
        $answer->formatedData = $data;
        switch($type){
            case "email":
                $answer->type = "email";
                break;
            case "options":
            case "number":
                $answer->type = "numeric";
                $answer->formatedData = intval(trim($data));
                break;
            case "date":
                $answer->type = "date|date_format:Y-m-d";
                break;
            default:
                $answer->type = "string";
                $answer->formatedData = strval($data);
                break;

        }
        return $answer;
    }

    /**
     * @desc crea o actualiza un registro en la tabla directories
     * @param array $data
     * @param int $formId
     * @param int $clientId
     * @param int $clientNewId
     * @param array $indexForm
     * @return mixed
     */
    private function addToDirectories(array $data,int $formId,int $clientNewId, array $indexForm){
        $newDirectory = Directory::updateOrCreate([
            'form_id' => $formId,
            'client_new_id' => $clientNewId,
            'data' => json_encode($data)

        ],[
            'rrhh_id' => auth()->user()->rrhh_id,
            'form_index' => json_encode($indexForm)
        ]);

        return $newDirectory;
    }

    public function uploadClientDataFromEmail(Request $request)
    {
        $this->validate($request, [
            'form_id' => 'required|integer|exists:forms,id',
            'email'   => 'required|email',
            'rrhh_id' => 'required|integer'
        ]);

        $formId = $request->form_id;
        
        $FormController = new FormController();
        $prechargables = $FormController->searchPrechargeFields($formId)->getData();
        $fileInfo['prechargables'] = [];
        
        foreach($prechargables->section as $section){
            foreach($section->fields as $field){
                if($field){
                    $prechargedField = new stdClass();
                    $prechargedField->id = $field->id;
                    $prechargedField->label = $field->label;
                    array_push($fileInfo['prechargables'], $prechargedField);
                }
            }
        }
        
        $fieldsLoad = $this->getSpecificFieldForSection($fileInfo['prechargables'], $formId);

        $answerFields = (Object)[];
        $formAnswerClient=[];

        foreach ($fileInfo['prechargables'] as $assign){
            foreach ($fieldsLoad as $key => $field) {
                if ($field->id == $assign->id) {
                    $fieldsLoad[$assign->label] = $field;
                    $data = 'No registra';

                    if (isset($field->client_unique) && $field->client_unique) {
                        $data = $request->email;
                    }

                    unset($fieldsLoad[$key]);
                    $field->value=$data;
                    $answer=new stdClass();
                    $answer->in=[];

                    if(isset($field->isClientInfo) && $field->isClientInfo){
                        $answer->informationClient= (object)[
                            "id" => $field->id,
                            "value" => $field->value
                        ];
                        array_push($answer->in,'informationClient');
                    }

                    if(isset($field->client_unique) && $field->client_unique){
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

                    if(isset($field->preloaded) && $field->preloaded){
                        $answer->preload=[
                            "id" => $field->id,
                            "key" => $field->key,
                            "value" => $field->value
                        ];
                        array_push($answer->in,'preload');
                    }

                    $answer->formAnswer = (Object)[
                        "id" => $field->id,
                        "key" => $field->key,
                        "preloaded" => $field->preloaded,
                        "label" => $field->label,
                        "isClientInfo" => $field->isClientInfo,
                        "client_unique" => isset($field->client_unique) ? $field->client_unique : false,
                        "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value,
                        "controlType" => $field->controlType,
                        "type" => $field->type
                    ];

                    $answer->formAnswerIndex = (Object)[
                        "id" => $field->id,
                        "value" => gettype($field->value) !=="string" ?  strval($field->value) : $field->value
                    ];

                    $answer->success=true;
                    $answer->Originalfield=$field;

                    foreach ($answer->in as $in) {
                        if (!isset($answerFields->$in)) {
                            $answerFields->$in = [];
                        }
                        
                        array_push($answerFields->$in, $answer->$in);
                    }
                    array_push($formAnswerClient, $answer->formAnswer);
                }
            }
        }

        $clientsManager = new ClientsManager;

        $data = [
            "form_id" => $formId,
            "unique_indentificator" => $answerFields->uniqueIdentificator[0],
        ];

        $client = $clientsManager->findClient($data);

        if(empty($client)){
            $data['information_data'] = $answerFields->informationClient;
    
            $client = $clientsManager->updateOrCreateClient($data);
    
            if(isset($client->id)){
                $saveDirectories = $this->addToDirectories($formAnswerClient, $formId, $client->id, $data['information_data']);
            }
        }

        $structureAnswer = [];
        $formAnswerIndexData = [];
        $formAnswerAux = [];

        foreach ($formAnswerClient as $answer) {
            $formAnswerIndexData[] = [
                'id' => $answer->id,
                'value' => $answer->value
            ];
            $formAnswerAux[$answer->id] = $answer->value;
            unset($answer->type);
            unset($answer->controlType);
            $structureAnswer[] = $answer;
        }

        $sections = Form::find($formId)->section()->get([
            'id',
            'name_section',
            'type_section',
            'fields',
            'collapse',
            'duplicate',
            'state',
        ]);

        $sections->map(function ($section) use ($formAnswerAux) {
            $section->fields = json_decode($section->fields);
            return $section;
        });

        foreach ($sections as $index => $section) {
            $fields = $section->fields;
            foreach ($fields as $key => $field) {
                if (isset($formAnswerAux[$field->id])) {
                    $fields[$key]->value = $formAnswerAux[$field->id];
                }
            }
            $sections[$index]->fields = $fields;
        }

        $formAnswer = FormAnswer::formFilter($formId)->clientFilter($client->id)->first();
        $chanel = Channel::nameFilter('Email')->first();

        if (!$formAnswer) {
            $formAnswer = FormAnswer::create([
                'structure_answer' => json_encode($structureAnswer),
                'form_id' => $formId,
                'channel_id' => $chanel->id,
                'rrhh_id' => $request->rrhh_id,
                'client_new_id' => $client->id,
                'form_answer_index_data' => json_encode($formAnswerIndexData),
            ]);
        }

        return response()->json([
            'form_answer_id' => $formAnswer->id,
            'preguntas' => json_encode((Object) ['sections' => $sections]),
            'client_id' => $client->id
        ], 200);
    }
}
