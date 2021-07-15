<?php

namespace App\Services;

use App\Events\NewDataCRMLead;
use App\Models\ApiConnection;
use App\Models\Client;
use App\Models\Directory;
use App\Models\Section;
use App\Models\KeyValue;
use App\Models\NotificationLeads;
use App\Traits\RequestService;
use App\Traits\RequestServiceHttp;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DB;
use stdClass;

use function GuzzleHttp\json_decode;

class DataCRMService
{
    use RequestService;
    public $baseUri;
    private $formId;
    private $tokenVicidial;
    private $productVicidial;
    private $constant = [
        'accounts'=>1,
        'potentials'=>2,
    ];


    use RequestServiceHttp;



    public function getToken($username){

       $response = $this->get('/webservice.php?operation=getchallenge&username='.$username);
       return $response;

    }

    public function login(){
        $apiConnection = ApiConnection::where('form_id',$this->formId)
                                        ->where('api_type',10)
                                        ->where('status',1)
                                        ->first();

        if(!$apiConnection) throw new Exception("Configuracion de Api no encontrada", 1);

        $this->baseUri = $apiConnection->url;
            $credentials = json_decode($apiConnection->json_send);
            $tokenVicidial = json_decode($apiConnection->parameter);
            $token = $this->getToken($credentials->username);
            $tokenValue = $token->result->token;

            $requestBody = 'operation=login&username='.$credentials->username.'&accessKey='.md5($tokenValue.$credentials->user_pass);

            $loginResponse = $this->post('/webservice.php', $requestBody);
            $this->tokenVicidial = $tokenVicidial->token;
            $this->productVicidial = $tokenVicidial->producto;
            $data = array(
                'expireTime'=>$token->result->expireTime,
                'sessionName'=>$loginResponse->result->sessionName,
                'userId'=>$loginResponse->result->userId,
                'baseUri'=>$apiConnection->url,
                'tokenLeadVicidial'=>$tokenVicidial->token,
                'productVicidial'=>$tokenVicidial->producto
            );
            Cache::forever('data_crm_session-'.$this->formId, $data);
            return $loginResponse->result->sessionName;

    }

    public function getSessionName(){
        $token = Cache::get('data_crm_session-'.$this->formId);
        $now = Carbon::now();

        if($token && !is_null($token['expireTime'])){
            if($token['expireTime'] < $now->timestamp ){
               return $this->login();
            }else{
               $this->baseUri = $token['baseUri'];
               $this->tokenVicidial = $token['tokenLeadVicidial'];
               $this->productVicidial = $token['productVicidial'];
               return $token['sessionName'];
            }
        }else{
           return $this->login();
        }
    }

    public function getCountAccounts(){

        // 'webservice.php?operation=query&sessionName={{sessionName}}&query=select%20*%20from%20Contacts;'
        $sql = rawurlencode("select count(*) from Accounts where createdtime>='2021-07-15 00:00:00';");
        $requestBody = "/webservice.php?operation=query&sessionName=".$this->getSessionName()."&query=".$sql;
        $countAccounts = $this->get($requestBody);
        $leadMios = KeyValue::where('form_id',$this->formId)->groupBy('client_id')->get();
        $diffLead = $countAccounts->result[0]->count - count($leadMios);
        return $diffLead;
    }

    public  function getAccounts($formId){
            $this->formId = $formId;
            $diffLead = $this->getcountAccounts();
            if( $diffLead != 0){
                if($diffLead > 100){

                    $cicles = 0;
                    $ciclesTotal = $diffLead / 100;
                    do {
                        $cicles ++;
                        $requestBody = array(
                            'operation'=>'query',
                            'sessionName'=>$this->getSessionName(),
                            'query'=> 'select c.*,p.potentialname as count from Contacts as c inner join Potentials as p on p.potentialname order by id desc limit '.$diffLead

                        );
                        $leads =  $this->request('GET', '/webservice.php', $requestBody);
                        // $this->setClients($leads['result']);
                    } while ($cicles <= $ciclesTotal);


                }else{

                    $sql = urlencode("select * from Accounts order by id desc limit ".$diffLead.";");
                    $requestBody = '/webservice.php?operation=query&sessionName='.$this->getSessionName().'&query='.$sql;
                    $leads =  $this->get($requestBody);
                    //Log::info($leads->result);
                    $this->setAccounts($leads->result, $formId);
                }
            }
    }


    public function getPotential($contactId){
        $sql = urlencode("select * from Potentials where related_to = ".$contactId.";");
        $requestBody = '/webservice.php?operation=query&sessionName='.$this->getSessionName().'&query='.$sql;
        $potential =  $this->get($requestBody);
       // Log::info( $potential );
        if(!$potential->success) throw new Exception("Error Processing Request", 1);

        return $potential->result;
    }

    public function setAccounts($leads, $formId){

        foreach ($leads as $keyLEad => $value) {

            $potential = $this->getPotential($value->id);
            $clientClean = $this->transformValues($value,1);
            $ponteialClean = $this->transformValues($potential[0],2);
            $keysToDirectory = [];

             /**
            *   SOLO PARA PRUEBAS DE DEMOSTRACION, ESTO SE DEBE ELIMINAR UNA VEZ SE TERMINE LA DEMOSTRACION ##########################
            *   solo para ambientes de pruebas
            */
            // if(env('APP_ENV') == 'local' ||env('APP_ENV') == 'dev'){
            //     if($keyLEad == 0){
            //         $phone = '3207671490';
            //     }else if($keyLEad == 1){
            //         $phone = '3152874716';
            //     }
            //     $clientClean['phone'] = $phone;
            // }
            // Quitar las lineas 156 a 163 para produccion

            $dataClient = [
                'first_name'=>$clientClean['firstName'],
                'middle_name'=>'',
                'first_lastname'=>$clientClean['lastName'],
                'second_lastname'=>'',
                'document'=>'',
                'phone'=>$clientClean['phone'],
                'email'=>$clientClean['email'],
                'document_type_id'=>1
            ];

            $create = true;
            $client = Client::where('phone',$dataClient['phone'])->where('email',$dataClient['email'])->first();
            if($client){
                Client::whereId($client->id)->update($dataClient);
                $create = false;
            }else{
                $client = Client::create($dataClient);
            }
            //,'potential-id1','fase-de-venta','descripcion','origen-del-negocio'
            $keysToSave = ['firstName','lastName','phone','email','account-id0','tipo-producto8','potential-id1'];
            $keysToSaveLocal = Section::getFields($formId, $keysToSave);

            foreach ($keysToSaveLocal as $key => $value) {
                $keyValue = null;
                if($value->key != 'tipo-producto8' && $value->key != 'potential-id1'){
                    $valueDynamic = $clientClean[$value->key];
                }else{
                    $valueDynamic = $ponteialClean[$value->key];
                }
                $keyValueToSave = [
                    'form_id' => $this->formId,
                    'client_id' => $client->id,
                    'key' => $value->key,
                    'value' => $valueDynamic,
                    'description' => null,
                    'field_id' => $value->id //TODO: ???????????
                ];
                if($create) KeyValue::create($keyValueToSave);
                if(!$create) $keyValue = KeyValue::where('field_id',$value->id)->where('client_id',$client->id)->first();
                if($keyValue){
                    KeyValue::whereId($keyValue->id)->update($keyValueToSave);
                }
                $keysToDirectory[] = array(
                    'id'=>$value->id,
                    'value'=>$valueDynamic,
                    'key'=>$value->key
                );
            }

            if($create){
                Directory::create([
                    'data'=>json_encode($keysToDirectory),
                    'user_id'=>env('USER_ID_CREATOR_DIRECTORIES_CRM_LEAD'), //NOTE: ID DE USUARIO QUEMADO EN EL .ENV POR AHORA
                    'form_id'=>$this->formId,
                    'client_id'=>$client->id
                ]);
            }else{
                $directory = Directory::where('form_id',$this->formId)->where('client_id',$client->id)->first();
                if($directory){
                    Directory::whereId($directory->id)->update([
                        'data'=>json_encode($keysToDirectory),
                        'user_id'=>env('USER_ID_CREATOR_DIRECTORIES_CRM_LEAD'), //NOTE: ID DE USUARIO QUEMADO EN EL .ENV POR AHORA
                        'form_id'=>$this->formId,
                        'client_id'=>$client->id
                    ]);
                }
            }


            /**
             * Es necesario crear un registro en la base de datos para controlar las notificaciones
             *
             */
            $notificationLeadByCLient = NotificationLeads::where('client_id',$client->id)->where('form_id',$this->formId)->first();
            if(!$notificationLeadByCLient) NotificationLeads::create(['client_id'=>$client->id,'phone'=>$clientClean['phone'],'form_id'=>$this->formId]);

           $newLeadVicidial = array(
               "producto"=>$this->productVicidial, // "leads"
                "token_key"=>$this->tokenVicidial,
                "Celular"=>$clientClean['phone']
           );

            $this->newLeadVicidial($newLeadVicidial);

            /**
             * Implementado unicamente para pruebas controladas, Solo se estan escribiendo 2 lead
             */
            if((env('APP_ENV') == 'local' ||env('APP_ENV') == 'dev') && $keyLEad == 0){
                break;
            }


        }
        /**
         * Despues de haber creado las notificaciones entonces se envia a front para se ejecuta el get notification
         */
        sleep(4);
        event( new NewDataCRMLead(  $this->formId   ) );

    }

    public function transformValues($values,$typeValue){
        $valueClean = array();
        if($typeValue == 1){
            //Account
            $valueClean = array(
                'firstName'=> $values->accountname,
                'middle_name'=> null,
                'lastName'=>$values->accountname,
                'second_lastname'=> null,
                'email'=>$values->email1,
                'phone'=>$values->cf_951, // $values->cf_951 TODO ################################# CAMBIAR PARA PRUEBAS, SOLO PARA PRUEBAS ####################
                'account-id0'=>$values->id
            );
        }else if($typeValue == 2){
            //Potentials
            $valueClean = array(
                'tipo-producto8'=>$values->cf_1041,
                'potential-id1'=>$values->id,
                'fase-de-venta'=>$values->sales_stage,
                'descripcion'=>$values->description,
                'origen-del-negocio'=>$values->potentialsorigin_pick

            );
        }

        return $valueClean;
    }


    public function newLeadVicidial($params){
        Http::post(env('SERVICE_SYNC_VICIDIAL').'/cos/services',$params);
    }

    public function filedsPotentialsForms(){
        $data = $this->get('/webservice.php?operation=describe&sessionName='.$this->getSessionName().'&elementType=Potentials');
       return $data->result->fields;
    }
    public function filedsAccountsForms(){
        $data = $this->get('/webservice.php?operation=describe&sessionName='.$this->getSessionName().'&elementType=Accounts');
        return $data->result->fields;
    }

    /**
     * Metodo que construye array con match del formulario de gestion de DATA CRM y el form answer de la tipificacion de miso
     */
    public function matchFields($formAnwersArr,$typeMatch){
        if($typeMatch == $this->constant['potentials'])   $fieldsExternals = $this->filedsPotentialsForms();
        if($typeMatch == $this->constant['accounts'])   $fieldsExternals = $this->filedsAccountsForms();

        $arrToMarch = [];
        $dataJson = new stdClass;
        foreach ($formAnwersArr as $keyAnswer => $valueAnwer) {
           $keyAnswerClean = $this->cleanString($valueAnwer->label);
            foreach ($fieldsExternals as $key => $value) {
               $labelClean = $this->cleanString($value->label);
               if($keyAnswerClean == $labelClean){
                   if( $value->type->name == 'date'){
                    $dataJson->{$value->name} = Carbon::parse($valueAnwer->value)->format('Y-m-d');
                   }else if($value->type->name == 'picklist' && is_int( $valueAnwer->value )){
                    $dataJson->{$value->name} = $this->matchPickList($valueAnwer->value,$value->type->picklistValues);
                   }else{
                    $dataJson->{$value->name} = $valueAnwer->value;
                   }
               }
               if( $keyAnswerClean == 'ciudad' ){
                    $dataJson->bill_city = $this->findAndFormatValues($this->formId,$valueAnwer->id,$valueAnwer->value);
               }
           }
        }
        $dataJson->accountname = $this->concatName($formAnwersArr);
        return $dataJson;
    }

    private function matchPickList($key,$options){
        return  $options[ $key -1 ]->value;
    }

    private function concatName($formAnwersArr){
        $fullName = '';
        foreach ($formAnwersArr as $keyAnswer => $valueAnwer) {
            if($valueAnwer->key == 'firstName')  $fullName .= $valueAnwer->value.' ';
            if($valueAnwer->key == 'middleName')  $fullName .= $valueAnwer->value.' ';
            if($valueAnwer->key == 'lastName')  $fullName .= $valueAnwer->value.' ';
            if($valueAnwer->key == 'secondLastName')  $fullName .= $valueAnwer->value;
        }
        return $fullName;
    }

    public function updateAccounts($formId,$formAnwersArr,$accountId){
        $this->formId = $formId;
        $fieldToMatch = $this->matchFields($formAnwersArr,$this->constant['accounts']);
        $accountDetails = $this->get('/webservice.php?operation=retrieve&sessionName='.$this->getSessionName().'&id='.$accountId);
        $responseAccounts= collect($accountDetails->result);
        $fieldToMatchCollect = collect($fieldToMatch);
        $merged = $responseAccounts->merge($fieldToMatchCollect);
        $requestBody = array(
            'operation' => 'update',
            'sessionName' => $this->getSessionName(),
            'element' => $merged->toJson()
        );
        if(env('APP_ENV') == 'local' ||env('APP_ENV') == 'dev') Log::info( $requestBody );
        $this->post('/webservice.php', http_build_query($requestBody));
        return;
    }



    public function updatePotentials($formId,$formAnwersArr,$potentialId){
        $this->formId = $formId;
        $fieldToMatch = $this->matchFields($formAnwersArr,$this->constant['potentials']);
        $potentialDetails = $this->get('/webservice.php?operation=retrieve&sessionName='.$this->getSessionName().'&id='.$potentialId);
        $responsePotentials = collect($potentialDetails->result);
        $fieldToMatchCollect = collect($fieldToMatch);
        $merged = $responsePotentials->merge($fieldToMatchCollect);
        $requestBody = array(
            'operation' => 'update',
            'sessionName' => $this->getSessionName(),
            'element' => $merged->toJson()
        );
        if(env('APP_ENV') == 'local' ||env('APP_ENV') == 'dev') Log::info( $requestBody );
        $this->post('/webservice.php', http_build_query($requestBody));
        return;

    }



    public function cleanString($string){
        $string = str_replace(' ','-',$string);
       $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
       'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
       'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
       'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
       'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        $str = strtr( $string, $unwanted_array );
        $str = strtolower($str);
        return trim($str);

    }

    public function getDataProductionTest($formId){

        $this->formId = $formId;
        $sql = urlencode("select * from Accounts where createdtime>='2021-07-07 00:00:00' order by id desc;");
        $requestBody = '/webservice.php?operation=query&sessionName='.$this->getSessionName().'&query='.$sql;


        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->baseUri.$requestBody,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        //Log::info($response);
        curl_close($curl);

        $responseJson = json_decode($response);
        if(!$responseJson->success) throw new Exception("Error Processing Request", 1);

        return $response;


    }


    private function findAndFormatValues($form_id, $field_id, $value)
    {
        $fields = json_decode(Section::where('form_id', $form_id)
        ->whereJsonContains('fields', ['id' => $field_id])
        ->first()->fields);
        $field = collect($fields)->filter(function($x) use ($field_id){
            return $x->id == $field_id;
        })->first();

        if($field->controlType == 'dropdown' || $field->controlType == 'autocomplete' || $field->controlType == 'radiobutton'){
            $field_name = collect($field->options)->filter(function($x) use ($value){
                return $x->id == $value;
            })->first()->name;
            return $field_name;
        }elseif($field->controlType == 'datepicker'){
            return Carbon::parse($value)->setTimezone('America/Bogota')->format('Y-m-d');
        }else {
            return null;
        }
    }


}
