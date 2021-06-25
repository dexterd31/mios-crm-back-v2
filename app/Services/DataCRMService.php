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

use function GuzzleHttp\json_decode;

class DataCRMService
{
    use RequestService;
    public $baseUri;
    private $formId;
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
            $token = $this->getToken($credentials->username);
            $tokenValue = $token->result->token;

            $requestBody = 'operation=login&username='.$credentials->username.'&accessKey='.md5($tokenValue.$credentials->user_pass);

            $loginResponse = $this->post('/webservice.php', $requestBody);

            $data = array(
                'expireTime'=>$token->result->expireTime,
                'sessionName'=>$loginResponse->result->sessionName,
                'userId'=>$loginResponse->result->userId,
                'baseUri'=>$apiConnection->url
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
               return $token['sessionName'];
            }
        }else{
           return $this->login();
        }
    }

    public function getCountAccounts(){

        // 'webservice.php?operation=query&sessionName={{sessionName}}&query=select%20*%20from%20Contacts;'
        $sql = rawurlencode("select count(*) from Accounts where createdtime>='2021-06-18 00:00:00';");
        $requestBody = "/webservice.php?operation=query&sessionName=".$this->getSessionName()."&query=".$sql;
        $countAccounts = $this->get($requestBody);
        $leadMios = KeyValue::where('form_id',$this->formId)->groupBy('client_id')->count();
        $diffLead = $countAccounts->result[0]->count - $leadMios;
        return $diffLead;
    }

    public function getAccounts($formId){
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
            */
            if($keyLEad == 0){
                $phone = '3207671490';
            }else if($keyLEad == 1){
                $phone = '3152874716';
            }else if($keyLEad == 2){
                $phone = '3185746575';
            }
            $clientClean['phone'] = $phone;

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
                    'user_id'=>1,
                    'form_id'=>$this->formId,
                    'client_id'=>$client->id
                ]);
            }else{
                $directory = Directory::where('form_id',$this->formId)->where('client_id',$client->id)->first();
                if($directory){
                    Directory::whereId($directory->id)->update([
                        'data'=>json_encode($keysToDirectory),
                        'user_id'=>1,
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
               "producto"=>"leads",
                "token_key"=>"123456789",
                "Celular"=>$clientClean['phone']
           );

            //$this->newLeadVicidial($newLeadVicidial);

            if($keyLEad == 2){
                break;
            }
           

        }
        /**
         * Despues de haber creado las notificaciones entonces se envia a front para se ejecuta el get notification
         */
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
                'phone'=>'3207671490', // $values->cf_951 TODO ################################# CAMBIAR PARA PRUEBAS, SOLO PARA PRUEBAS ####################
                'account-id0'=>$values->id
            );
        }else if($typeValue == 2){
            //Potentials
            $valueClean = array(
                'tipo-producto8'=>$values->cf_1041,
                'potential-id1'=>$values->id
            );
        }

        return $valueClean;
    }
   

    public function updateContact($params){

    }
    public function updateNegocio($params){

    }

    public function newLeadVicidial($params){
        Http::post('https://app.outsourcingcos.com/webservice-dinamico/cos/services',$params);
    }


}
