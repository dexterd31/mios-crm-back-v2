<?php

namespace App\Services;

use App\Events\NewDataCRMLead;
use App\Models\ApiConnection;
use App\Models\Client;
use App\Models\Form;
use App\Models\Section;
use App\Models\KeyValue;
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
                    $this->setAccounts($leads->result);
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

    public function getFields($formId){

        $keysToSave = ['first_name','first_lastname','phone','email','source_data_crm_account_id'];

        $sql = Section::where('form_id', 2);

        foreach ($keysToSave as $key) {
            $sql->orWhereJsonContains('fields', ['key'=>$key])->where('form_id', 2);
        }
        $sections = $sql->get();
        $sections1 = collect();
        $fields = collect();

        foreach ($sections as $section) {
            $sections1->push(json_decode($section->fields));
        }

        // $field = collect($fields)->filter(function($x) use ($key){
        //     return $x->key == $key;
        // })->first();

        dd($sections1);
    }

    public function setAccounts($leads){

        foreach ($leads as $key => $value) {

            $potential = $this->getPotential($value->id);
            $clientClean = $this->transformValues($value,1);
            $ponteialClean = $this->transformValues($potential[0],2);

            Log::info($clientClean);
            Log::info($ponteialClean);

            $dataClient = [
                'first_name'=>$value->accountname,
                'middle_name'=>null,
                'first_lastname'=>$value->accountname,
                'second_lastname'=>null,
                'document'=>null,
                'phone'=>$value->cf_951,
                'email'=>$value->email1,
            ];
            Log::info('Data a la base de datos');
            Log::info($dataClient);
            //$client = Client::create($dataClient);

            $clientId = 1; //FALSO POR AHORA NO QUIERO GUARDAR A DATA BASE
            $keysToSave = ['first_name','first_lastname','phone','email','source_data_crm_account_id'];
            $keysToSave2 = ['product_type','source_data_crm_potential_id'];
            foreach ($keysToSave as $key => $row) {
                $keyValueToSave = [
                    'form_id' => $this->formId,
                    'client_id' => $clientId,
                    'key' => $row,
                    'value' => $clientClean[$row],
                    'description' => null,
                    'field_id' => '1123213123213213213' //TODO: ???????????
                ];
                Log::info($keyValueToSave);
            }
            foreach ($keysToSave2 as $key => $row) {
                $keyValueToSave = [
                    'form_id' => $this->formId,
                    'client_id' => $clientId,
                    'key' => $row,
                    'value' => $ponteialClean[$row],
                    'description' => null,
                    'field_id' => '1123213123213213213' //TODO: ???????????
                ];
                Log::info($keyValueToSave);
            }

            /**
             * Es necesario crear un registro en la base de datos para controlar las notificaciones
             *
             * $notification =
             *
             *  event( new NewDataCRMLead( $notification->id, $this->formId ,$clientId  ) );
             */




        }

    }

    public function transformValues($values,$typeValue){
        $valueClean = array();
        if($typeValue == 1){
            //Account
            $valueClean = array(
                'first_name'=> $values->accountname,
                'middle_name'=> null,
                'first_lastname'=>$values->accountname,
                'second_lastname'=> null,
                'email'=>$values->email1,
                'phone'=>$values->cf_951,
                'source_data_crm_account_id'=>$values->id
            );
        }else if($typeValue == 2){
            //Potentials
            $valueClean = array(
                'product_type'=>$values->cf_1041,
                'source_data_crm_potential_id'=>$values->id
            );
        }

        return $valueClean;
    }

    public function setKeyValues($leads){

    }

    public function updateContact($params){

    }
    public function updateNegocio($params){

    }


}
