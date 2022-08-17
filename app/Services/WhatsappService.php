<?php

namespace App\Services;

use App\Models\WhatsappAccount;
use App\Traits\RequestService;

class WhatsappService
{
    use RequestService;

    public $baseUri;
    public $secret;
    public $whatsappAccount;
    public $source;

    public function __construct(WhatsappAccount $whatsappAccount)
    {
        $this->baseUri = config('services.whatsapp.base_uri');
        $this->whatsappAccount = $whatsappAccount;
    }

    public function sendTemplateMenssage(string $destination, string $templateId, array $messageParams)
    {
        $headers = [
            'Content-type' => 'application/x-www-form-urlencoded',
            'apikey' => $this->whatsappAccount->apikey
        ];

        $data = [
            'channel' => 'whatsapp',
            'destination' => $destination,
            'source' => $this->whatsappAccount->source,
            'template' => [
                'id' => $templateId,
                'params' => $messageParams
            ],
        ];

        if (env('APP_ENV') == 'development') $data['src.name'] = $this->whatsappAccount->app_name;

        $this->request('POST', '/sm/api/v1/template/msg', $data, $headers);
    }
    
    public function getTemplates()
    {
        $headers = [
            'Content-type' => 'application/x-www-form-urlencoded',
            'apikey' => $this->whatsappAccount->token
        ];

        return $this->request('GET', "/sm/api/v1/template/list/{$this->whatsappAccount->app_name}", [], $headers);
    }
}