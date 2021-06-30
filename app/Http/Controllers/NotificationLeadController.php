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
    public function setReaded($formId,$rrhhId){


       $leads = NotificationLeads::where('form_id',$formId)->where('readed',0)->first();
       if(!$leads) return;

       NotificationLeads::where('id',$leads->id)->update([
            'read_at'=>Carbon::now(),
            'read_by'=>$rrhhId,
            'readed'=>1
        ]);
        $notification = NotificationLeads::where('id',$leads->id)->first();
        event( new NewDataCRMLead(  $notification->form_id   ) );
        return $notification;
    }
}
