<?php

namespace App\Http\Controllers;

use App\Events\NewDataCRMLead;
use App\Models\NotificationLeads;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationLeadController extends Controller
{
    //Lista de las notificaciones sin leer

    public function getNotifications($formId){
        return NotificationLeads::where('form_id',$formId)->where('readed',0)->get();
    }


    /**
     * Establecer notificaciones como leidas
     */
    public function setReaded($notificationId,$rrhhId){
        $notification = NotificationLeads::whereId($notificationId)->update([
            'read_at'=>Carbon::now(),
            'readed_by'=>$rrhhId
        ]);
        event( new NewDataCRMLead(  $notification->form_id   ) );
        return $notification;
    }
}
