<?php

namespace App\Services;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\RequestService;

class NotificationsService{

    use RequestService;
    private $baseUri;
    private $secret;


    public function __construct()
    {
        $this->baseUri = config('services.notifications.base_uri');
        $this->secret = JWTAuth::getToken();
    }

    public function sendEmail(string $body, string $subject, array $to, array $attatchment = [],array $cc =[], array $cco = [], string $origin){
        $request = new \stdClass();
        $request->to = $to;
        $request->subject = $subject;
        $request->body = $body;
        $request->cc = $cc;
        $request->cco = $cco;
        $request->attachment = $attatchment;

        $params = array(
            'idType' => 2,
            'origin'=>$origin,
            'request'=>$request
        );

        $this->request('POST','/api/notification/send', $params);
    }
    
    public function sendSMS(string $message, array $addressee, string $origin = 'DEFAULT_SMS')
    {
        $request = new \stdClass();
        $request->body = $message;
        $request->to = $addressee;

        $params = [
            'idType' => 1,
            'origin'=> $origin,
            'request'=> $request
        ];

        $this->request('POST','/api/notification/send', $params);
    }

    public function getEmailsByCampaing($id)
    {
        return $this->request('GET', "/api/campaings/emails/$id");
    }

    /**
     * Metodo para el envio de notificaciones al front
     * @param $idNotification, $app, $user , $message
     * @return request
     */
    public function sendNotification($idNotification ,$app, $user, $message)
    {
        $requestBody = ['idNotification' => $idNotification,'app' => $app, 'user' => $user,'message'=>$message];
        return $this->request('post', '/api/testPusher?'.http_build_query($requestBody));
    }

}
