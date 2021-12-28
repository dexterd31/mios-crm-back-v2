<?php

use App\Models\Notifications;
use App\Models\NotificationsType;
use Illuminate\Database\Seeder;

class AddNotifications extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $to = [];
        echo("ingrese el formId relacionado con la notificación:\n");
        $formId = fgets(STDIN);
        echo("ingrese el id de la sección al que está relacionado el envío");
        array_push($to,fgets(STDIN));
        $activators = json_encode([
            (object)["id"=>1334567890,"value"=>"l","type"=>"option"],
            (object)["id"=>98766544,"value"=>"20211222T05:09:56","type"=>"date"],
            (object)["id"=>1334567890,"value "=>"3","type"=>"option"],
            (object)["id"=>6557643787,"value"=>"","type"=>"text"]
        ]);

        NotificationsType::insert([
            [
                "id" => 1,
                "notification_name" => "email",
                "rrhh_id" => 1
            ],
            [
                "id" => 2,
                "notification_name" => "sms",
                "rrhh_id" => 1
            ]
        ]);

        Notifications::insert([
            'form_id' => $formId,
            'notification_type' => 1,
            'name' => 'test',
            'activators' => $activators,
            'to' => json_encode($to),
            'subject' => 'test@test.com',
            'template_to_send' => 'señor $nombre: esto es una prueba',
            'rrhh_id' => 1,
        ]);

    }
}
