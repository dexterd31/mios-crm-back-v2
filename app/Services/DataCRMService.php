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
            //if(!$loginResponse->success)  throw new Exception($loginResponse->error->message, 1);

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
                    Log::info($leads->result);
                    $this->setClients($leads->result);
                }
            }
    }

    public function getContact($accountId){
        Log::info($accountId);

        $sql = urlencode("select * from Contacts where account_id = '11x".explode('x',$accountId)[1]."';");
        Log::info($sql);
        $requestBody = '/webservice.php?operation=query&sessionName='.$this->getSessionName().'&query='.$sql;
        $contact =  $this->get($requestBody);
        Log::info( $contact->result );
        if(!$contact->success) throw new Exception("Error Processing Request", 1);

        return $contact->result->contact_id;
    }
    public function getPotential($contactId){
        $sql = urlencode("select * from Potentials where contact_id = '13x".explode('x',$contactId)[1]."';");
        $requestBody = '/webservice.php?operation=query&sessionName='.$this->getSessionName().'&query='.$sql;
        $potential =  $this->get($requestBody);
        Log::info( $potential );
        if(!$potential->success) throw new Exception("Error Processing Request", 1);

        return $potential->result;
    }

    public function setClients($leads){

        foreach ($leads as $key => $value) {
            Log::info( $value->account_id );

            $contactId = $this->getContact($value->id);
            $potential = $this->getPotential($contactId);
            Log::info(json_encode($potential,true));

            /*$client = Client::where('phone',$lead['phone'])->where('document',$lead['contact_id'])->first();
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
            }*/
        }

    }


    public function setKeyValues($leads){

    }

    public function updateContact($params){

    }
    public function updateNegocio($params){

    }


}
