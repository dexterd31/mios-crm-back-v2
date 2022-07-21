<?php

namespace App\Services;

use App\Traits\RequestService;

class WhatsappService
{
    use RequestService;
    public $baseUri;
    public $secret;

    public function __construct()
    {
        $this->baseUri = config('services.whatsapp.base_uri');
    }

    public function sendMenssage(string $apikey, string $source, string $destination, string $message, array $options = [])
    {
        $headers = [
            'Content-type' => 'application/x-www-form-urlencoded',
            'apikey' => $apikey
        ];

        $data = [
            'channel' => 'whatsapp',
            'destination' => $destination,
            'source' => $source,
            'message' => [
                'type' => 'text',
                'text' => $message
            ],
        ];

        if (isset($options['src.name'])) $data['src.name'] = $options['src.name'];

        if (isset($options['disablePreview'])) $data['disablePreview'] = $options['disablePreview'];

        $this->request('POST', '/msg', $data, $headers);
    }
}