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

    public function listNotificationType()
    {
        return $this->request('GET', "/api/notificatonsType/show/{appName}");
    }

    public function listOrigins(int $idNotificationType)
    {
        return $this->request('GET', "/api/$idNotificationType");
    }

}
