<?php

namespace App\Services;

use App\Models\ApiConnection;
use App\Models\Client;
use App\Models\Form;
use App\Models\KeyValue;
use App\Traits\RequestService;
use App\Traits\RequestServiceHttp;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function GuzzleHttp\json_decode;

class DataCRMService
{
    use RequestService;
    public $baseUri;
    private $formId;
    use RequestServiceHttp;



    public function getToken($username){

       $response = $this->get('/webservice.php',['operation'=>'getchallenge','username'=>$username]);
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
            //Log::info($credentials);
            $token = $this->getToken($credentials->username);
            //Log::info($token->result);
            $tokenValue ='60d23b9ae6e42';
            Log::info($tokenValue);
            Log::info($credentials->user_pass);
            Log::info($tokenValue.$credentials->user_pass);
            $requestBody = array(
                'operation'=>'login',
                'username'=>$credentials->username,
                'accessKey'=> md5($tokenValue.$credentials->user_pass)
            );
            Log::info($requestBody);

            $loginResponse = $this->post('/webservice.php', $requestBody);
            //if(!$loginResponse->success)  throw new Exception($loginResponse->error->message, 1);

            $data = array(
                'expireTime'=>$token['result']['expireTime'],
                'sessionName'=>$loginResponse['result']['sessionName'],
                'userId'=>$loginResponse['result']['userId']
            );
            Log::info($loginResponse);
            Cache::forever('data_crm_session-'.$this->formId, $data);
            return $loginResponse['result']['sessionName'];

    }

    public function getSessionName(){
        $token = Cache::get('data_crm_session-'.$this->formId);
        Log::info($token);
        $now = Carbon::now();
        if($token && !is_null($token['expireTime'])){
            if($now->timestamp <= $token['expireTime']){
               return $this->login();
            }else{
                return $token->sessionName;
            }
        }else{
           return $this->login();
        }
    }

    public function getCountContacts(){

        // 'webservice.php?operation=query&sessionName={{sessionName}}&query=select%20*%20from%20Contacts;'

        $requestBody = array(
            'operation'=>'query',
            'sessionName'=>$this->getSessionName(),
            'query'=> 'select count(*) as count from Contacts'

        );
        $countContacts = $this->request('GET', '/webservice.php', $requestBody);
        $leadMios = KeyValue::where('form_id',$this->formId)->groupBy('client_id')->count();
        $diffLead = $countContacts - $leadMios;
        return $diffLead;
    }

    public function getContacts($formId){
            $this->formId = $formId;
            $diffLead = $this->getCountContacts();
            if( $diffLead != 0){

                if($diffLead > 100){

                    $cicles = 0;
                    $ciclesTotal = $diffLead / 100;

                    do {
                        $cicles ++;
                        $requestBody = array(
                            'operation'=>'query',
                            'sessionName'=>$this->getSessionName(),
                            'query'=> 'select * as count from Contacts order by id desc limit '.$diffLead

                        );
                        $leads =  $this->request('GET', '/webservice.php', $requestBody);
                        $this->setClients($leads['result']);
                    } while ($cicles <= $ciclesTotal);


                }else{
                    $requestBody = array(
                        'operation'=>'query',
                        'sessionName'=>$this->getSessionName(),
                        'query'=> 'select * as count from Contacts order by id desc limit '.$diffLead

                    );
                    $leads =  $this->request('GET', '/webservice.php', $requestBody);
                    $this->setClients($leads['result']);
                }




            }

    }

    public function setClients($leads){

        foreach ($leads as $key => $value) {
            $lead = json_decode($value,true);
            $client = Client::where('phone',$lead['phone'])->where('document',$lead['contact_id'])->first();
            if(!$client){
                Client::create([
                    'first_name'=>$lead['firstname'],
                    'middle_name'=>null,
                    'first_lastname'=>$lead['lastname'],
                    'second_lastname'=>null,
                    'document'=>$lead['contact_id'],
                    'phone'=>$lead['phone'],
                    'email'=>$lead['email'],
                    'document_type_id'=>null
                ]);
            }else{
                Client::whereId($client->id)->update([
                    'first_name'=>$lead['firstname'],
                    'middle_name'=>null,
                    'first_lastname'=>$lead['lastname'],
                    'second_lastname'=>null,
                    'document'=>$lead['contact_id'],
                    'phone'=>$lead['phone'],
                    'email'=>$lead['email'],
                    'document_type_id'=>null
                ]);
            }
        }

    }


    public function setKeyValues($leads){

    }

    public function updateContact($params){

    }
    public function updateNegocio($params){

    }


}
