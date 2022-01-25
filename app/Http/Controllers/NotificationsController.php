<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Notifications;
use App\Models\NotificationsAttatchment;
use App\Models\NotificationsType;
use App\Services\NotificationsService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotificationsController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @author Juan Pablo Camargo Vanegas
     * @desc Muestra todos los registros almacenados en notifications.
     * @return \Illuminate\Http\Response
     */
    private function index($external = true)
    {
        $notifications = Notifications::all();
        foreach ($notifications as $notification){
            $notification->activators = json_decode($notification->activators);
        }
        if(!$external){
            return $notifications;
        }
        return $this->successResponse($notifications);
    }

    /**
     * @desc Almacena la información recibida en notifications.
     * @author Juan Pablo Camargo Vanegas
     * @param  \Illuminate\Http\Request  $request
     * @param  bool $external : indica si la función será utilizada directamente en la ruta (true) o en un controlador (false)
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$external = true)
    {
        $this->validate($request,[
            'form_id' => 'required|numeric',
            'notification_type' => 'required|numeric',
            'name' => 'required|string',
            'to' => 'required|array',
            'template_to_send' => 'required|string',
            'activators' => 'required|array',
            'activators.*.id' => 'required|int',
            'activators.*.type' => 'required',
            'activators.*.value' => 'required',
        ]);

        $newNotification = Notifications::create([
            'form_id' => $request->form_id,
            'notification_type' => $request->notification_type,
            'activators' => json_encode($request->activators),
            'name' => $request->name,
            'subject' => (isset($request->subject))?$request->subject:'',
            'to' => json_encode($request->to),
            'template_to_send' => $request->template_to_send,
            'rrhh_id' => Auth::user()->rrhh_id
        ]);

        if (isset($request->signature)) {
            $newNotification->signature = $this->signature();
            $$newNotification->save();
        }

        if(!isset($newNotification->id)){
            return $this->errorResponse('No se creó la notificación',204);
        }
        if(!$external){
            return $newNotification->id;
        }
        return $this->successResponse($newNotification->id);
    }

    /**
     * @desc Muestra todos los registros almacenados según el form id.
     * @author Juan Pablo Camargo Vanegas
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showByFormId(int $formId,$external = true)
    {
        $notifications = Notifications::where('form_id',$formId)->get();
        foreach ($notifications as $notification){
            $notification->activators = json_decode($notification->activators);
        }
        if(!$external){
            return $notifications;
        }
        return $this->successResponse($notifications);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @desc retorna las notificaciones y tipo de notificaciones que contenga un formulario
     * @author Juan Pablo Camargo Vanegas
     * @param int $formId : id del formulario a consultar
     * @return \Illuminate\Http\JsonResponse : objeto con la data solicitada
     */
    public function prepareNotifications(int $formId){
        $response = new \stdClass();

        $form = Form::where('id', $formId)
                    ->with(["section" => function($q){
                            $q->where('state', '!=', 1);
                        }])
                    ->first();
        if(!$form){
            return $this->errorResponse('form not found',404);
        }
        $form->filters = json_decode($form->filters);
        $form->fields_client_unique_identificator = json_decode($form->fields_client_unique_identificator);
        $form->seeRoles = json_decode($form->seeRoles);
        for ($i = 0; $i < count($form->section); $i++) {
            unset($form->section[$i]['created_at']);
            unset($form->section[$i]['updated_at']);
            unset($form->section[$i]['form_id']);
            $form->section[$i]['fields'] = json_decode($form->section[$i]['fields']);
        }
        $notification_types = NotificationsType::all();
        if(count($notification_types) === 0){
            return $this->errorResponse('notifications type not found',404);
        }
        $notifications = Notifications::where('form_id',$formId)->get();
        /*if(count($notifications) === 0){
            return $this->errorResponse('notifications not found',404);
        }*/
        foreach ($notifications as $notification) {
            $notification->to = json_decode($notification->to);
            $notification->activators = json_decode($notification->activators);
        }
        $response->form = $form;
        $response->notifications_type = $notification_types;
        $response->notifications = $notifications;
        return $this->successResponse($response);
    }

    /**
     * @desc almacena las notificaciones junto con sus archivos si estos son enviados
     * @author Juan Pablo Camargo Vanegas
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|void
     */
    public function saveNotifications(Request $request){
        // descomentar para fase 2
        /*if(str_contains($request->header('content-type'),'multipart/form-data')){
            $multipartRequest = [
                'activators' => json_decode($request->activators)
            ];
            if(isset($request->atatchments)) $multipartRequest['atatchments'] = json_decode($request->atatchments);
            if(isset($request->to)) $multipartRequest['to'] = json_decode($request->to);
            $request->merge($multipartRequest);
        }*/
        $this->validate($request,[
            'form_id' => 'required|numeric',
            'notification_type' => 'required|numeric',
            'name' => 'required|string',
            'body_notifications' => 'required|string',
            'to' => 'required|array',
            'activators' => 'required|array',
            'activators.*.id' => 'required|numeric',
            'activators.*.type' => 'required',
            'activators.*.value' => 'required',
        ]);
        $notificationType = NotificationsType::where('id',$request->notification_type)->get();
        if(count($notificationType) == 0){
            return $this->errorResponse('Notification type not found',404);
        }
        $request->merge([
            'template_to_send' => $request->body_notifications,
        ]);
        $savedNotification = $this->store($request,false);
        //validación de archivos adjuntos
        if(isset($request->attachments) && !empty($request->attachments)){
            $validAtachment = false;
            foreach ($request->attachments as $key=>$attachment){
                $attatchments = [];
                 // descomentar para fase 2
                /*if(Storage::exists($atatchment['route'].$atatchment['file_name'])){
                    return $this->errorResponse("route {$atatchment['route']} is invalid",400);
                }*/
                if($key == 'static'){
                    $this->validate($request,[
                        "attachments.static.file_name" => 'required|string'
                    ]);
                    $attatchments['static_atachment'] = $attachment['file_name'];
                    $attatchments['route_atachment'] = $attachment['route'].'/';
                    $validAtachment = true;
                }
                if($key == 'dynamic'){
                    $this->validate($request,[
                        "attachments.dynamic.file_name" => 'required|array'
                    ]);
                    $attatchments['dinamic_atachment'] = json_encode($attachment['file_name']);
                    $attatchments['route_atachment'] = $attachment['route'].'/';
                    $validAtachment = true;
                }
                if($validAtachment){
                    $attatchments['notifications_id'] = $savedNotification;
                    NotificationsAttatchment::create($attatchments);
                }
            }
            if(!$validAtachment){
                return $this->errorResponse('atatchments has one or more invalid arguments',400);
            }
        }
        $response = new \stdClass();
        $response->message = "notificación creada exitosamente";
        $response->notificationId = $savedNotification;
        $response->notifications = $this->index(false);
        return response()->json($response);
    }

    /**
     * @desc método para el envío de notificaciones según la respuesta del formulario
     * @author Juan Pablo Camargo Vanegas
     * @param int $formId
     * @param $formAnswerData
     * @return void
     */
    public function sendNotifications(int $formId,$formAnswerData){
        $notifications = $this->showByFormId($formId,false);
        if(count($notifications) == 0){
            return;
        }
        foreach ($notifications as $notification){
            $sendEmail = true;
            foreach ($notification->activators as $activator){
                $existingActivator = $this->activatorsInResponse($formAnswerData,$activator);
                if(!$existingActivator){
                    $sendEmail = false;
                }
            }
            //envío de notificación
            if($sendEmail){
                switch($notification->notification_type){
                    case 1: //email
                        $this->sendEmailNotification($formId,$notification,$formAnswerData);
                        break;
                    case 2: //sms
                        break;
                }
            }
        }
    }

    /**
     * @desc valida que el activador de la notificación se encuentre en las respuestas del formulario
     * @author Juan Pablo Camargo Vanegas
     * @param $formAnswerData
     * @param $activator
     * @return array|false
     */
    private function activatorsInResponse($formAnswerData,$activator){
        $structureAnswer = json_decode($formAnswerData->structure_answer,true);
        $activators = array_filter($structureAnswer,function ($data) use ($activator){
           return ($data['id'] == $activator->id) && strtolower($data['value']) == strtolower($activator->value);
        });
        if(count($activators) > 0){
            return $activators;
        }
        return false;
    }

    /**
     * @param $notification
     * @return void
     */
    private function sendEmailNotification($formId,$notification,$formAnswerData){
        $attatchments = [];
        $notificationService = new NotificationsService();
        $nAttatchments = NotificationsAttatchment::where('notifications_id',$notification->id)->get();
        if(count($nAttatchments) > 0){
            $dinamicAttatchments = [];
            $staticAttatchments = [];
            foreach ($nAttatchments as $attatchment){
                if(!is_null($attatchment->dinamic_atachment)){
                    array_push($dinamicAttatchments,['name' => json_decode($attatchment->dinamic_atachment),'route' => $attatchment->route_atachment]);
                }
                if(!is_null($attatchment->static_atachment)){
                    array_push($staticAttatchments,['name' => $attatchment->static_atachment,'route' => $attatchment->route_atachment]);
                }
            }
        }
        $emailBody = $notification->template_to_send;
        $to = (isset($notification->to))? json_decode($notification->to) : null;
        $formController = new FormController();
        foreach (json_decode($formAnswerData->structure_answer,true) as $data){
            $formatedAnswer = $formController->findAndFormatValues($formId,$data['id'],$data['value'],true);
            if(isset($formatedAnswer->name)){
                $data['value'] = $formatedAnswer->name;
            }else{
                $data['value'] = $formatedAnswer->value;
            }
            $emailBody =  str_replace("[[{$data['key']}]]",$data['value'],$emailBody);
            $notification->subject =  str_replace("[[{$data['key']}]]",$data['value'],$notification->subject);
            $emailBody = $this->getSignature($formAnswerData, $emailBody);
            $notification->subject = $this->getSignature($formAnswerData,$notification->subject);
            if(!is_null($to)) $to = str_replace($data['id'],$data['value'],$to);
            if(isset($dinamicAttatchments)){
                array_walk_recursive($dinamicAttatchments,function (&$attatchment) use ($data){
                    $attatchment = str_replace($data['id'],$data['value'],$attatchment);
                });
            }
        }
        $expresion = '/(\[\[\w+\]\])|(\[\[[a-z0-9-]+\]\])/m';
        $emailBody = preg_replace($expresion,'',$emailBody);
        if(isset($dinamicAttatchments) || isset($staticAttatchments)){
            foreach ($dinamicAttatchments as $attatchment){
                $attatchment['name'] = implode("",$attatchment['name']);
                $existAttachment = $this->existAttachment($attatchment['name'],$attatchment['route']);
                if(!$existAttachment) break;
                $content = Storage::get($attatchment['route'].'/'.$existAttachment);
                $attatchment['file'] = $content;
                array_push($attatchments,$attatchment);
            }
            foreach ($staticAttatchments as $attatchment){
                $existAttachment = $this->existAttachment($attatchment['name'],$attatchment['route']);
                if(!$existAttachment) break;
                $content = Storage::get($attatchment['route'].'/'.$existAttachment);
                $attatchment['file'] = $content;
                array_push($attatchments,$attatchment);
            }
        }
        if(!is_null($notification->signature)) {
            $signature = $notification->signature;
        }
        $emailTemplate = view('email_templates.genericMail',compact('emailBody', 'signature'))->render();
        $notificationService->sendEmail($emailTemplate,$notification->subject,$to,$attatchments);

    }

    /**
     * Retorna el texto enviado con los datos de creacion, actualizacion y firma del agente
     * @author Edwin David Sanchez Balbin
     *
     * @param int $notificationId
     * @param string $emailBody
     * @return string
     */
    private function getSignature(object $formAnswer, string $text) : string
    {
        $signature = auth()->user()->rrhh->name;
        $createdAt = $this->formatedDate($formAnswer->created_at);
        $updatedAt = $this->formatedDate($formAnswer->updated_at);
        $text =  str_replace("[[signature_crm_2022]]",$signature,$text);
        $text =  str_replace("[[created_at]]",$createdAt, $text);
        $text =  str_replace("[[updated_at]]",$updatedAt,$text);
        return $text;
    }

    private function formatedDate(string $date){
        return Carbon::parse($date)->timezone('America/bogota')->format('Y-m-d H:i:s');
    }


    /**
     * @desc valida la existencia del archivo según su nombre y ruta (opcional)
     * @author Juan Pablo Camargo Vanegas
     * @param string $fileName: nombre del archivo con su extensión
     * @param string|null $path: rúta del archivo (en caso de que tenga más carpetas en el storage)
     * @return false|mixed|string
     */
    private function existAttachment(string $fileName, string $path = null){
            $fileName = !empty($path) ? $path.'/'.$fileName : $fileName;
            $fileData = explode('.',$fileName);
            $fileExist = glob("../storage/app/$fileData[0]*.$fileData[1]");
            $fileExistData = explode('/',$fileExist[0]);
            return count($fileExist) > 0 ? end($fileExistData) : false ;
    }

    private function signature()
    {
        return '
            <tr>
                <td align="center" valign="top">
                    <table width="600" cellpadding="0" cellspacing="0" border="0" class="container">
                        <tr>
                            <td align="left" valign="top">
                                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOkAAABaCAYAAABdaXE4AAAAAXNSR0IArs4c6QAAIABJREFUeF7snQWUXdUV939Xnr9545KZeHCS4O5FSinSUoq0QKBYcS3VECChxS1QHIIEDe5SirW4BZJAjHgyk7E3z69+a5/7XjIJUOjX9a2vhbmsWRPe3Hvfueee/9b/3kfzfd9n4BiYgYEZ+K+dAW0ApP+172ZgYAMzoGZgAKQDC2FgBv7LZ2AApP/lL2hgeAMzMADSgTUwMAP/5TMwANL/8hc0MLyBGRgA6cAaGJiB//IZGADpf/kLGhjewAz8fwfpN6VpNU0beEsDM/C9noEBkH6vX//Aw/8vzMAASP8X3tLAGL/XMzAA0u/16x94+P+FGRgA6f/CWxoY4/d6BgZA+r1+/QMP/78wA/8xSP/T6Ox/ev3/wiQPjHFgBv6TGdB83/FB0hz619ynUsn25VSIBzgEfw/JXeSf/U735JJyCkX9U05c+3Zyk6845Fp1ffmQ0akRqut9fK1yK03dd41797/fqvOCD9c+L7iPv/oe/ce4xn2+oqJPLg7u+O2Of+PUb3fDgbO+DzOgWW7GN/UIhYJLJBLFc6FYtEhWhfF8F33VwhKIrLnKbCCNjYFOtadj2D5YrgKRF9ZxwwZO+RLTB8MHvQxKV5dFr+FbPoam47keolUF6EbcoORDUfOwNQ9D04iiEfFAdz3wPeyQhifXiYCQEfjgFkrono9hhsAwwPfxDQ1XAC+jku/3QcaiBIoGJcNW6A+hrxYC8h2uG1wg91GSIRifAroIHl0HzQQMcMFzXPV3XdfR1N/k8uB8z/Nw5H4ahMJhdBmaB7ZtY5pmWY756r5yjfznyW8/+C2f67oR3FvdUK6Xv3sYYRnfwPFdngGtp2+Fn0zUYpV0YlFTrUfLAsOEfKFINBpercvWUia2ARlDLVNqbAhZSrWqwwmDEwJbC6AdcsEUkPkoLejowe8KeNV1cqL81sENQ1ZzcUMCHp+YD2HXRxMAaT6FELiahuXYRM0I0TJQsTxwXDzPx/E9wlVRRJgUS7YCS9Q0iQjwPPCsInrUKA+qgkVBuw+eDNQHR76vsgQqE1BGuEKijNcE0wRDBJlc7+K4Dm4ZZNF4DE2+UxNgOmochmFgmob6GjmUMFx1ewGnpwSLK2jWdAVUTdPVXMsfRNgpoRYqT/B3eZV+z59Ns13bN3STrpUWhbxHIh5VSiIUhkLBJxItr9D+pmx50lwdnLKiiXpgiLKQ9S2LS3AgQJQF6EFI/l5ehKLZBOACUlnXRtEnkrHQLQcnm6WIQ3L9NoiB4F7uEnI9tPKKdg2fnOnjKKQ5GKJltRAhTHRXw1dKS0MLQSZfIhQNY5jyiWhqB0NOcGwoWlAQTWoEIJOHFi0pILXswCrIFpSmxBPtKoCRQRsQDkFIh4Ro7Yp5IBJGHtaEWESd47quMtvFoFa4V9Op4doCZI9IPKl0eFknl1W+skVwNF8JIrleI9CiFUtAlzHJIRcOmNHfaRhr6b68Xyr6THvwGV5+6U1sSyMcihKOmBSLOSy7FKwCX1AliKysCFmYsuzlM71spoGva+WFKOvVV8A0PV/9yNmeLDzdL2tSh2TUI2ZZNFgaVb6Oqfskmqo56OSjaN1oJHoypMw6zRHkiZmp4RgaBbkHDqLnPddC8zTCegRTCymTXWknUylVhRlNwFPMg1NCoVjMT1vAqkFXL86yZSxbupSV7SvJdKcpZnN4RZtSXx7XcrCKJWzHVnjVTZNwLIYeMYlVx6kb1EjbsGE0Dh1MsrEOrToJVQmIRSFi4nsulpinuq6uNfSQmjuBdFFFBHTC8uy+pn7EFvaQeQLPMAIZURZWYnmEfC0QePLT/5V8p5fq9/fhNM/zfduCyy6+i1tvvo9SQaeqqkb5SqVSkWQyXgamgLS/X+qh+xoh18RHp2D4WEYAIGWWuT4hxyPiaZiep0xWWVUCULcMVAMLq2sRLWGTwUaMpAaWXyLeUs34my6jbvSQ8psRjekqgPqyaOV7lAxwMDUbzfMw9TAahhpLyXGxHEeBOxGNoItZWyqBa0OhCEuWkvlkBstnf870t/6Bl8tjZ/N4+RKG5aE7Pr7t4cozRKPKZC/hY+livvsKOHoohKGFMByDkuXSZxXIiZauraJp1HCGjd2IhhHDGLPDNsQb6gg11kMkgu/YFG0bzdAxIlHyIro0g7DMpacrl0BTZnKgSfVwSD1r4EUEIlGALIG6NaNd399F/F1/ci2X9UX5ceVl93PXlEeJhuupTjWqQI7jONiOVbbRAo0ZqEkR/x6mpxG1TQSCmbBPwdSwzSDiGXEgYnvEXJH6ogc8pUU93cPXAq0adUvUlNIMT8VpjsZx7TxLM8vJxVwuu+evNK3XBhG5nwRtPKWJlH0sgSB1Sw/NLWLIZ7qOK9pK0zDFoVa+oQe9vcFq7klT/Gg60196mTnvvEuxvZOY75IIaWiOhSs+q+0Gmt8Mo4cj+KZJr1XCqEoQqa0mXF1FKB5XqlnFgwVUThjTFNPWVIGuPrtEj1Ug7djkdZ9ep8TgdUex6TZbs/Hmm9EyZDBEI8rflci4Ew7j64YY6spqFsEnFkjgjwpIA01asW5l3lSkW/4mAaUBW/e7jlE0xw5AeulfHuaeO58kHmkBP0KmL4dhmIQjodUg7Z9y0Fwl9WO2qfymvggBSCU+4mvEbIi6PmFHoO3h6eKbqRirAm3I9amxStR2dTK0OkG4KkyP18dSt5NiDUx5+j4SLXE0FePxlKUrGlqZsaJUlXoJkkCYGo5rU3JKhMIGYfEXs1lo74LONPOefYk3H32K7rkLqDUj1McTSvu6pRJhw1C+c1H3KYV0tJokVYNbaFxnBMnWFppGDsdIxknU1GJWV0MiCRI9rgSNlMkpgSOJoIlwKIeRleHgg21hlUqUHJtwJEokWTaDVWrKWx1wkmdTkWTRrHo5oCRWicAysPRl+l0V+w3+UwE5FVsfOL7LM6BZlu+Lsrzowik8cO/z1FQNBy9GqehSW1tHLpctP3850qOWRqBJDc8nYmtKw2UjGnnxARUINeKOpqKx4ovKuY7u4ss1vkfE9UjYHvUFm02jKapMn3Ytw+e5JSzSOvGbQjz2yhNEa/UgYFrWHkE2VzQfaBJRcgW+ngoK4TvgWkGUKpvB+vAjFr/zAS/fdT/xTImUZ1AViimTOF+yKLoOnmESqa5i0KgRjNp8DDUbrguDm6CxBuqqoToRgE+lVMpWhAJfOT8qvwRXYoqrtE05MqwCT+LvBpFZHCf4ccu/JWgl1oXcu64OTF35rioYFTZxRXCo79PVXKpsVRBvUtaImMG2CC4gqnTwwPFdngHNcX1f4igXT5rK1LueJREdjKGlKBYcotEYnorClMX4KidIgOcqk0tSKxJ9zIc0bBU0Eh8Uok4AULne8kp4YY1YNETItjEyOWptjWFajDGxBnp6VrIommWB2cOycJpQa4ypD0+hvjlcDkt5Kojpux5hwwxyuQWLWCKMUDHsrEVcFr78zJ3Nwmee5dPnnqNr5mcMqaqBkoPjaxQxyBohIs0trLPZZgzffDNSm2wAzfUBKCXnaGggaadwYDJ7rosuUV8ZiQJLOUdqu/ilgsRplbZUALU9SOdg6QrcBUvIt3dipftId6ykfdky+vp6cT1HfYfru+R9n0hTEwf8ahzaZmNQ0qelHi8aJW2ViIbjhCSgJBMsDyoPLpZFyFC+sYs3ANLvMjorstnxXN93dS6edDdT73yORHTIapBG4orQEKzO/kANtKNETDXxFSXgo8xSkfoSpZS8qK9ALD6oozl4pmhYl0ipRLLk0KZFGWxU0eDEWZntZVEiz6JwLx3hNNFBUR546A4aGgwkDSjhIBXFtR10MTU1yNkOZsjELfrEZQDdafx332P6ww8z7+WXSWT7GNHUyMruTnpKRUpVSZo3Hs062+1Iy2ZbwpARATBrohCXdEqQx5TIryNEgrLACemmMt+VU1gUQaCSSkFkuJjHXjiHzoUL+GLmZ3TOW4DV0YUnY+nuQ8vk0ApF6hNJalNJfM+hL5MmV8gpgPnRKFqqji5DY8uf7cuY48bB0GZczcWKhCm5Pkkjjik5K/le0daiNkO6InmI6RvRQwNe6XccqJrjlXzfDXPxpDuZetdzJCJDyyB1y5pUqACVUGJZk5Qjjb4mJmxgxoqHJP8ZwjzyJFhUieZ66IodZOMV8sRLJVqMCEPDSWqNJLm0z4pSjo4qi85Ihl69l6rmCA8+cDtN9ZpKRQqLSJclaVlohokfDpHGUYGWKgm55l06HnyUV2+9HXPeAuodG6/Yhx1yydZEad5qLOv8cHeattomAGe8FvRYYIpSVP6yRFs10Z7KB/RxPCfI+YrfKv6v5UPegfZeCjPnMPPN95g381NWrJiP4dkYjkvU8Uh4EHc9opaDUSxRn4hTSPdiOXmiZoja+hpiiShFq0Q6W6QnbVGIJ8m1VDP4Bzuw1VGHENp0YxwRQKEoOiE0sUpUeFc55uWobkCN1CS/O3B8p2dAc/wySCdOYepdz/cDacXctZXWXH2UWTbKP3JxJYIjqQIFzACkyodSuBaTzCEcFr+shJ/LUovG4HiSRjOM75gs6/NZ4dhkUw7ZcJ6i3kd1fYhp99+KZC3EbVNRXFFljoXjuGiREE7EJCKaLW0x/46p/H3KVLSl7YyKJQg5JYqahT6ohm2P/BnRLcfAlptAsgrXM8gVRTtHVRRXtLwprCYBp+uieS6hCu1PQsglG3IFSnMX8OHLr/PeC6/QPns+KT1Ec22Kzs4lVMUjxEMhoTUpzRn3fWpCEapDJrpVIuy56JJjLRVxinnEvwgL88mM4Vg6w8aM5bXF83izezlbH3UYe5xwDOa6I6GmBl8LqVyvuNqG2LriH5eVuaIpVUD7nV6m3++H0xzP8n03FGjSO58vm7vVFAs20WgUz5cITRCVXZ2YC4JHEq11hWYkwSIBqacHpuGqs0UV2UTELLMsorZFYyRMUyKOblt05SyW+XFWSvK+CmyzgOOnqa0NMe2+m2lsAC0sMRYXTWxoz8EpFjHFRxRCQHsX1gOP8uAlV2D1ZRmz8UYsWLiQ7lKOHX76Y0bv90PYcStIxfFDOj2ecIFDRCJVRAgpCzbvB1kekQWu8Jddn6iwICTwk87Q/dHHvP/6G7z56itke3tpG9LKiBHDiSZieKUCSc0jKoEcyyLd1UlfZydWJoPd14efzRK2LOpDYRoiMarRidguYdtV7kBY03FtR6V5sokYS01YHDEZsceu7HTkL4lsMhZS1Srq6yqtryntviqQJIBVQP1+L+Lv+tNra/qkoknLPmlRQBopg7TM91uVgukHUj2IMioNKrkM8VTLFSySejEsm4TrUuO41GgadZEQEQNyxT6W5kp0JhrpEjcroeHpBVy3l/oaAemN1DeqHAO25uKFXJXKMYoWum9CVxbeepeXfv87vCVLCNfVktZgQS7HZvv8kJ1PPAHWGRn4mokYpXCIolDsxJ3WRScZiiCQcyTLqym/NuWWI6m9BZjxOStnfs6cd9+jpbaaYUNaMQYPgvqqwIdNRIJ8pwgLSb1IQEjYTCLMSgVYvoyexYuZ+dZb9MxfSM+cL9C7+mjSwjSGYkQtD83K0hSL0FPoRotWExrUysy+Pmbmcmxy4AHs9rtzYHArJKP4oRDq7m6Qyw0rwpeQnxWdauD4Ds9Av+juvf180qpAk8akEkaWxleDVEgJkhKQVVLRoiLxVTBSF2aMR9xyqLY8BvkajaEQEc0lX8zQY/fRqel0J5roQSMck3hMEcfuprbGVCBtaALHhKLhUDAtDDxSlotZAN74lA9uvoXOFx5iZGM18/J55jsOo/c/gJ1OOBE2GgNSlVNTEwRbylx58TXFj9ZNDd3UKeGiuQ4JQWzBhQXLyPzjPb54/S16536hosZuPkuxlFXVAOHqGHWtDbSOHEps+EjM3feF+qYgheJZAZc2Hg5Uc0EGqkFHF8yYw4J/vMeSdz4iO28JkbxDo+aQyC1ncH0VXV1ZcsIDrGnko86VFAYN4gcnHkfroQdCUw1udZxcmXAvGjjqaorLrAvnceD4Ts/Aak068d5+5m6VKlcLNKmANODpBhFeyRkGpVti7gYgFS0aQvMlDhtoWU2ziLgOVY5Hte3Tasapi8YoWHnasyvpJkchGScdriHj+iSE4+oXsKxuamsNHrj/r9Q3K14FRcMjoxUwXYdGsb57HZx7n+Hec89ls7hFMqHxiVUgNHYMe599FtrOu0meAqLV+K6GLW6zlIlFAs0cAFZo/I46LSZ+bsGDuYtZ/vDTvPPgo5S+WERrLEbM9zAF1KoizSZXKpC3iyrCWqipx994SzbaZx/W3Xt3GNqiSn1KEkgTf1S+znbRJDVTErJ+CRYsJf/2h3zw0qssff9tBolQsopUG3FFMbQcHbe6lo5EjI+tPCc+9gAMacZvriUbEu3vEcZUpXtCyJDytS8fX6Va1yphWsN9+f+5xivxjvL6+sqh9K/uqDzb/wvzYe0qp/6B0rUH1i95/f94+jTXL/i+F+XiiXdz9x1B4MjUqyiVbMXcsewC8XiC3p4MVckaSiWLRDKGZefIljJEqyVS6eGXosTMJAkjQsgpoBc6CeV7qQFakrUkQinylkenZ2FVGbyzcDoNo4ZR0iK4JZ8qLayipI7XR7IGpj50DU2DwQ9Bbz5LKB4lJcu+Nw+zFvDYuBMZ2ttHdbodN+LzcdhhyAF7s+3ECVDbgFX0CSfrhXQbHOWqcU80nKr3kiSIBL58InkLugrM+POVzLzvEdYxTLTeburiYWwnX043BbJJUkwBh1mnaJh0GgbLQtC2545sf/IxsPkYlWfNiRGthxRJPxlNBGSGvr4ynxFYtBj7kxk8c+1k4u2dmCu62LhxEHqmQF+xRLammlmeQ9M+e7Lb5RdDTVIxoiRHmi/lSEUi2MUCsWhUPZ4QBBVbSUXVgwVfqU8N4glrVuUHS1zOX7tOeM3F/01tj6Xs7tsdXwUqETKV62UclVx0+Y6K/ilWXEArXfUi+7ldXy2kvt2IVOxklewKFE/wXf3iL+V3veYdK+fJDP57lszauwF/0/yqt+T6Od/34lw88Z5+IE1RktRD2CSXz1BTU6sYSJ6rqehqlVR46BZ9hR5KmkU4lkRzUjg5oQH61Ieg2k8TKfRQo/kMHzQUTU/xwewvWCb1n0ObWOZ1UzOkiZ7uDBQ9avyY0pS2lyFe63H3w1fRODioqy5YRcxwmJjUinZlsJ59mWd+dwGDMzmafJdCXOfzujB7T/gN4f32hupqHMfANMq1bjLvZZC64sLpPppKsZQrazrTLJr6KO/fdg+pL5YwVNcIW1mJJCnNKykm8bcNYQC5pvKJfd/E1TVKustyw2F5fZLWH/2ADQ85kJqddsTTTHLCWDQiRMJyjY9nF4NFKQtBCP8rVmK98R5znn6Bz555nqaiw4hIXBE2+kyT2WJqbzKG/SaeD2M3wk9GccI6Rc8irhs4dpGwRJXL6a8AdBXpvxZIVy2+tbSFqmzqr43/PZB+U/ub1Yv7q0GqCYFDgaJcrNdf4avC+QpI+2muVYIleMb/5FC0cnWLCvDWBuna81M5NyD5CIPt3wkK/F+CtJKCuZ+773hhtSa1CoTDBqGwpqphTCOKYUh9pEc63YOmO0STIdLFNMlUHSG9DqfgYdouNaZHwunByHWx/qBmdFsjm9OYvaQTGhrxG1J8kVtB1itRW10LRZdqP4bhOVheH/G61SAVM1M0nkxEWDRed5b3L76KBXc/RFvBIiXEhrjJguY4P39wCgyqx62twTHi2I5GQrSiNFlYpU3FIBealBUwhaSmdP5iHvrNn+j4x9tskkyRLORIhsHK92GGAwp7kP81lVmPAFSTrhMaXsinGA/zXsdyCq3NjD34IHY6+yyoqRVvF6TbhRRsK6qHBL9sNAGqhGgtB7oL8MyLvP3Agyx6402G+BqDktUUbI9lvsaKxnr2OOs0mg7cH+qqKIXEM/eJSjbXsdENPciXfq0mLS+6NXrb9FvW/Rb8Vy32rwfB2ubz/w1UVBlB+cJVJs7q4ncF0v7arSyA+mnS/xSkq0dd1qSrNHZgjfjqu9Z2Kfpr0i93LPnqmVgtTPoD9dvIGM31Hd93DC6eOI2773ixDNIkJStHWHITeolSKU8ymaJUlHYfITyJZGoO4XgIx7fJF22sUrCADdcl5hepj1g0xXQaImHSHUKHS1DUYtjJWpbks6RNm0h1Ek/aQJQcqoihezYlPwDpXQ9fRbOYuxLALJs7Zs5S5u4jx55M6O2PGWz76vv6YgZza0yOevx+aKlR7CI/kqLk+SQknaK6GEiKUXzlIEqseL6lInSspPdvr/DY5ZNJdHSzXiyB1b6cVNwkZPiqCkjJS1fyvyaaF8bHxNYlLRL4qdH6Wuan07SHQlhDBnPM5ZfBmNGQTCrzVHgQkikxdKFJWpTcIp7hkZK0ikSpO7px33qTx6+ZjDZvAaOStegln3wkxvxwiC2PPYp1TjoWapNkJOtimsSVghH+r1AxRfD0N3eDZRKYuxWQ9luO/SzUYJF/vZ/31SDor42/LTi/CtT9fc1KF6s1NWOgqb/eJ/02i/zrR9j/u/p/z2pzNwDp15yn5v3b+Mjf5px/MUrXK3N3L3x8tSY1BKQZwhGhr2fR9KAXz+LFS5RUGTy4jXRftzKFq1IJPF8nHq9TdaiOBFa6V1AdKjGkLolRKtK+uJ2ubotYqhWtupliJI6TiChqW6DNHJJaFHybImliokkfuYrmNsmROiplI4R0I1+C3gJ3HXwEjZ8tYIhnKDprh+4yJ+5z4s1Xw67bKZMwb4TwDQmxmME0yvWSwtGkJtNB86Qzg6RKlvPWFVfz+dMvMoIwLZaL27mSiOGTTMbIFnMqGCa+qKbM3DDCJLZ1UwXNfM9SQSInkiATS/J2RzunXT+Z8O47K9qhG4sqt1iMOakDELAKgcLSPEIiLDq7CUn1y6KFPHfBRLpf/SfDtQjRvEu8rpFPrCJb/GocI886ET8Vp1d1zYgSK3oYEjSSjjeyWMp51NWLulw5s0ozBItAYbYM6ADIq5gnX7lK/n1N+nUa9ps0b0UjrQ1SNeq1xlY5R567P4C/rcConLe2lvzy96xpnvYXToGPv3q0a5vd3/T//cfwr8etKZA6cPHEJ/uBNE7J6iMcdaitN5k9dzrJZIIxY0YTCkUIhUJ89vkMRowYRj6TQerJunqyLF6yFNe1GNrawHpD62lIRpg7fTrrjVifUjGE5cV5d+ZCzJomiqEQ7Ss7aaxOKU5sXI/i+zYFLU2s3lWatKVNQgmymKXZGUF0tCfLvYcfQ+LDzxjmm4T0EMvtIkuqQ+xy0tGM+NUvoC5F2rYJp2pVrWuZlKtMTrmfmJ1SjWNYBejp5MXzzufzx55nHTfEKN8kkSuoIFY4YpB3ispcDbpPyCiEBGHi6BLJli4pJit7e/Dj1RSqa/jMsTn2mitgl+2hKoZTFVctYHQ7aC8jaVsBlhDkpWpU8y1C4p92dfL+ldew6NGnqe/JE+7N0zB4GO9letnljJMZdOLROKkYWUljmTFCuYC7LGmh1SBd3fwsiLAHbVgqi9xX7RcDjRWANegcszoo82VAfBmkX6XpKousEpxae9F9PZC+7NOtqbnW9uECSbNauWmrfO1/F6CV8yum7Ff7tmsGltb+Dimh7C/k+t9jbQ3cb47WuM3Xnbf6pK8A6RAVcCnZacJRi2iixMzP3mezzcZw6WWXUFsTVum/Rx97lG222prdd22jtwduvuNZJl9/Ha2tzYz/0zlsvdmGzJk5nfun3MHvz/kjrSMa6V4BvzxmPF8s7yNc3UiyqoaSRDyLNjFdfDeLvAKpw90PX01LK4qtpPmFoCArV4LuDM+e+VtyL77OSN+kyjfo8xx6GpJoG45gq3GHktprt6A4vLoGy9dUJwVh/wbTGUQKZW6jXgm90M3Cp5/l8fGX0ri4mx2rmoiu7KZKuj+4RUWkkJyvADW4R3Av8UkDkAoZoUg2GmGh5lO303bsMuEPsO4IqKkiq5vK6xL+rzQmDMtbL9OGLMOlaPokXAujO82ca29g1pT7aMmU0NM5Yo2NfOwWOHDSeKIH7U8pEcWKJQhrYbRMEH0PAltBR0G1flcpg9ULKHhmjQCk0s5ltXJSIFULrT9AV2uUNUH6ZbNtzcDR2mAsd4tdQxOuGRlaDdK1tVgAnkornK9W84EL82VN++0BG7gDFeG1Vv9Xuc0q3K0V0Sp/RSAk+qeRKhd9FfjW0sSrqsq+WkCs0rVrmrtCCxysgkQlu5dwtES2sISaujB77Lkrfxo/Tr3gcATGj7+ZQi7PxReeofKPN9/6PBdd8md22nVHbrvtIjo78px47NGcdOyx7P3jPYOOYhqMO/pKPp/fQUfaRtci1Eh6ouQQNaK4WOT1NNF6h3umXc2gVqmX9PCcHGFDuLG20qT/mHgxi6c9yXqeQaI3g24YZOuTfNTXyXr77sUOf/od1NWAdFGISSeFsKodlUBOUCseLGpTc3ByHSR9uPlnR8O7s9i7YSjFOXMYFIlhlcRKCOiPagmruSyHgDQJ2OhYtkOovp6VUZM3u1bwgzNPZsMTj8VPxnFrG+gsFtCNCEnDlL5qQYS/XP0nGnWF20dzKITW3sPsi65gxi13MzpZg5sv0G36zE+FOfzqS2D3nciHQ3jxFNJyzUtbxGLhsrm7qr3xV4BUwkyB6vFVZzhjFUhVx9Jy/frqZb3mov9qkPY7+0tWbP8P1gZ/RSCUPxcXQqr6VwmI/t9dZq9V4kqVW621ngNL4P/2kJmRqKLctF/6Z+3v+hJQV4M56EPwbXK9/cfY/zm/hSbtSff61clq/vTbu3nikTeprxlFujeH6/eRSHl0dM1my6034q67LyWdhsZGEAv3vfenc/3kyUy5+RbCYfj7a29z30P3c/nVV1BTq/PDvQ7gJ/vty2mnHkdfV5FUdRS3VYXDAAAgAElEQVSxLqfc9TrX3TCVULSBvl6L6kgVXsklZgaaNCcgrXOYOu0a2lrFmrNwChniAmbxSYVT+893uePXp7Kh5TM8X1TEfasqwVLNZY5TYNiuO7LrMb+CrbZUfYWIJyEk5rSGJWEjISZI0Ec6MrgZmjWNnr+/y+vX3kbnS28wHIO2aBQ/m0Z3S1THo0i4qJjP4fiOMvlVQEgaA8ZTzEn30NmQYPi+ezL2iIMJb70lXiRCe65Iqqpe9VyS6HBYD2ptxdRW+T3DJ6+7xMU/fuNj5l13O5kXX6eqUCLnWyyPQmGTUfzk+ith5FCKsSieLqR8n5QZQmjVittc5tn3NwMrqQsJ8nV2dtLc1KpStXNmL+TD92dSU92kyCr1DVE22Wx9/vmPt1W7nM7OldTV1bLrbruQ6cuoWEQsHsOxXYrFUrnn1WqNLYu0ry9PLBYjFNJUfzdxsS3hKJd7AufzReLxqGpnalklEolE0I5U01TdciRiUpL62WgI2ymplqeRcJy//e0VigWXWDSJYYRVr+Htd9iCQsHiqacfp61tEM3NTYxaZ6RKF8qRyeSCFGHZqhADo6dH+ODVKvcfkU4j4jllJRgq/btclW6MROLksjbZTJHmZiHzwMcff8KcubOYN282g1qb6e7uYp99fsx6626grBh5hbbjEgoZ9PamiUUT6lmC+1skEmGKRZdYzMCSxUIQiEwkRFyrEX6r9I3m+a6/sqPAX695lEcfeh3cahKJlCIVZHLLGLPpUE4741jGjB3JddfdxHbbbcuuu21Cb6/NKSefyLhfHMkP99mZjvY+NFMnXp1k/HnnoWs6E8afx7LFi3n+uRf4xWFHkExEeeudRRx++MnU1o0AL06EGF7RJxaK4gpItV5idQ73TruGwa3ScMtBc0voEqWVihSp45w5mxcm/oXcG28ytmjR4Pm40SiZkMEy32G5oRHbcD2GbrctGx97XKD6Q1EU91DALj2BXegtFdGSBnWiybpyFF57i/fue5i5r75GJJ9laKoKr7eHqGURcSzimkGiKoEZC5O2i3TmcnSXHPricdp+uBM7n3gU7LgNBc8h4+mkqhop2Q6GZgZ9mMorR8Aq/FvXKbKsfQlDqurgb+/y8cXXYb87naZ4gg69xByzxBan/Yr1hCRRXUVenkOLSM0CSdNUv2VaghRM+Sj/IwCp2A5Bg21dC9HTk2Xu7CVcdslkZs6Yz/DhQ/n9H05lzNj1yWazHHbYYWywwfqcdNKJDJLWMc31qpmEUJOle4fqGiPL2gk4GbJIiyVX9Q8WCnGp5BGP62SyQXljPB5WgLXt4HyRl3LI/8shxBjp66wavoUlEu0EPc3x6e5K8+ILf+eC8/+CY2vU1TVyyy230NraomIFEydO4J133+Laa69lk002VPeTBu8CiFLJp6uri+bmBvW5arPsQT5vKcEgQJXxKh6G5iiBmc85GHqYSBhWrrTVfT/44D3GHfULttp6MxYvWcBf/vJnhg8fwbXXXK/mW54jGgt4KhUa9cqOLNFonKoqXX2ey9nE4yFs2yOe0NV3Ctcgl8+SSMQDl+UbDs1yir5VDHHrjU9y7ZVTCRsNtLYOZmXXInwty/kXnsWBB23O3fc8x/nnj+eUU0/izDOPVvGH4489kbaWNs468wxSdUm1WCZOupiX/vYKt9x8J3U19Vw0aSLPP/8cTz35OCNHtihzbO8fnk53t4ddlOhrEreklTVpibzWS7zO4b6HrmVwm4YhnRxMqXcOGmPr0pKku5flDz/Ka1dfw/Cly1gnHKWQK+JIO5TGRub29jC3lCcuEnbIEDbYfkc23G0PGLkumGXVE4tBMoiOFrrSxERDJ5PQ3UP61b/z2jNPsvijj0kWCtS70OjqqkF3vpijt5AlY7hY0Qih+maG77AtYw7el+jO2ypmUEb6Jzk6sVCCULkNqmhty3exfZuQrqpEMUp51YKGeUv57NIb+XTq42wUqUI3ND6TmMAWG7DPZefBlmNUS5WiIR2NpMTPDxp8Cw7L5t7XgTSXy5BMJFUEXqqdBGzHHXsOTz/5Mvvs8yPunnqRWjgrVvTyu9/9lgsvvIC2wS1qEa9Y0UVfXx+trW1UpcJk+xxWrGhX5JaGxrgCq+gCOXf27OVKO7a2tqoFK1PZ0+MQCpvq3/m8LOrALBSBVflMmphHIpoSAJZdxDB98vk8qapaxew6b/wlPDztSWzL57HHHmP99RtVGfDk625RWvTQQw9QWi8nxRqdnbS0tFBdHUyKrDWJn8jztbe3M2pUs/q8p8dWY+3LdFHfkKCqKkk+5xGNmOqZbr7pAS655BI22XQ0d0y5iYZGqT2G22+/gyefeJpHH52mHry721LfGRZTEhgyuEEJBFnb2WxOpb9aW6vVfKxoTyuhVlUVpyoVUgCuCL1vBGlPutuvrqrlikse5JYbp9HcsA4dHZ1YTpqjf3UoZ//mp3T1eEyYMJ4333qDo446gnFHHUZTS4JXXnmFm/56E5dddilNLY1KYh9y6OGcftq5/GDXbXjxhelMmnghK9qXcPMt1zNqnXVpbkoxaeIUnn7yDTRfuLVxXOmeXzZ3BaSJWocHHgxAqpcs9LCiAARmkDyxNK7+7HPeveYaFtx3HxtH48Qs+dwlUVVNXtNY5pTIJ+N8kc3iV1eTammjdb0NWGeTzWjdeDQMagn64jbUBmpI3mZeSPSgcj7pHli2lOLseayYPouuGXPQC5bSpEYqhpOKYCXi1K+zLq07bQ9j1lOaq0fzicVrkf5/ub4MVeF4wGYWSp/mYTkllQaS59CEzNDTx6Ip9/HK5DtIdRfZsKmNOcuXUBjawF5nn0j1z3+s8qMCUFdA6ku+VvxpTXV0NKTdSzknuqa5K+Ldo2TliYZjynKQ7UTEfTrhuAt45qlXOeigA7ns8lNYsrSHa665muNPOI4NNxpMX9qlp6eH2267lQ8++IC2tsGMGjWK+fO/4KOPPmbDDTbi7LPPYfSYIWrarrnmZlasWMHYsWP5/PPP6evLcPbZZ5NKpbj44ouZP1+09nAOPfRQZT7fc889fPHFF8oqO+XU43nv/Xe5Z+rt7LHnLuy087Y0NDQQDsUp5F3mfL6YU085h472bn7yk59y4cTTefvtGTzx1MNMmnSe0nzjx1+pzG0xo2UcohnFKth44zbuuecpnnzySXK5HL/5zW8UiF988UWeffZZ1fj9hpsup7GxAWkQL+n/uXN6+PUJJ/Ppp59w2eUXM+7oH7OivYeWllrVzPzBB6ax/34Hcsft9zB9+gxGjx6t0l8vPP8iW265NePHn8L77y9Uz93b28MWW2yuTO1PZ0xn6dLFbL/DNpx88q8Z1Fqv3IdEsmxe/AukaioDY8OEP97I31/6ALwEc+bM48f77s4NN50uhBk6u2R/GGiRRoKyhkXFeyKRunjxxZc49NBDlJTo68vy4Qcz2GG7bdQaV43cowGxJlEVmN/SxO+N12ZwwnHnUi8mL9U4JUOB1MeiqKVJ1jjc/+BkhrQK+1BsOg8rEqHgiEmikRTHR2o2X3uVp/88ifCipTTaJk1aFLunTw1Sr06S0XyWZvsI1dagR+P0lix6ihaR+nqGjN6ItvXWpYDPBqNHEx46JCg3kwL15rqgFC2XK5vYPvTkYd4Cvpg1g45cH80bjmL4FpvB+uuqUjjVF0lH7WHjaYaiEapu82UivOR7fVv2qik7bcUSrOxi+i23M+PZlzC6sgyub2ZFRxdpU2eHow5jvROOUk3RpAsj8YSKLKsewyrnKiB1VNCswtPt/56lYaioWglzyTtzXcnzRkh3wxmn/Yk3XvuQnXbakUsu/T033Hgto0dvzM8P3l35lBU67uTJt3LdddfT1tbGySefQqFQVNpk3tx5XHXV1Rx88B5cf8ODXHrpZYwfP57jjtufd99ZzJFHHsm+++7L6aefzhNPPMlFF13EiBEjuOGGGxk2rJ6TT/4djzzyCCeddAKXXX42n85YyPgJ53LxJRNZb/3hZYKAaLVgN4Lxf7qKp558gUg4wj1T7+S9999Umw0ccsjeXHXlXVx22ZVccMEFHHfcAbz++uecdtppbL311vz2t78lnU4zbtw45W/fdtttbLnlKF544V1OPPFEGptSPPzYFJWRCJsR1SLr1b/P5LTTzlTa749/Opd9fvwD4glN9aYqFPLU1qYo5OFHex/AwgVLefXV15UffOwxx5FOZ3j66Wdpbgpx7rmX8MCD97PPPntz4IE/4fPZs7jxxuupq6/myqsuY8cdN1MWQbkB5r9Upgqk0qT+qsvv4+4pj7NsSS977LEnF048m5HrwMt/n09X10oSiSSua/P57E/YfsfN2XHnjZU/JP23xMZ2nRLxaFwtzHf+uZD5czqpqW6mvX059U1Jlqz4jB/tswetrVVk0rD/vsdhGPWkMzp2GaRgU6KXZI1IrGsVSOULfGEixcJkXU8R4sO2Ra0s9u5OSq++wnPX3UjPuzPZIFZLXcElKh3zIya9hQyplkZ68zky+byK8JpJ0Uo6yws52nMZqgcNIlsoquLreCpF3dA2km3NKidZMqRpCyyYv5AVi5ZTlaxmzBZbsOUuOzFsmy1gcItqXma7tip2N1WZjY5l2aohmzKDfFc1u1YOTL4QSLdMlvz7H/HZK6/w2ZNPEc0XiURj9NoO3ZrGVgfsx7bHHwND2lQn/LxhYIRDqvLOFP9S9rhR5XdBN0LFe1nrbUs/waABaBDwiYZT6JpJ90r44+8v5b23P2Ps2DGMGTuM3//xLO6++y5+8pOdlTCW4VZVw8PT/q6Atu222zJt2s0qHXLmmZO49977mDDhfHbeeRfOPOtslixZyh133MGYMcMUyH/6kyP45JNPeOqppxk9uo1x487ks88+45JLLmWvvcZw0UV3cPfdd7P77rtyyWXn0dnVzm13XM8ll16o0l7pdC+xaIpYNK40//SPOvjdb8crTb7d9lszctRgfv+HX9PenmXckcfTvqJTaa699tqclSt9JSCKxSLXX389O++8AbvuerAyS6+77jp+8IONmDcvp86prolw973XMWqd4cFGHx7cf9+r/OmP51NbW8PpZ5zEL4/YM+jUqkE6naW6OqlM4pdefIemxjZGb9zGrFndnHnGWXR2dnPjjTez3bYt3HTTi/zhj7/niCN+yaWXnqkU3bHHHcXChfP5y8WT2GPPnZHaiCAF9q8NXq1Y8n0JjN1w3dP8eeJkGhuGcM3Vk9l6myg33fwyV119OZFIRC0CCUYI++gnB/6QCyedpvrVLlm+nFtuvZnDDzuEkcPXJWoYnH3q5bz75hxmzZivevrUNsTQwn2cdvrx/OIX+yhNd+Zpf+XJp14jVT8CyzKIG1LNIbTANKkaj4ceuJa2QZWmEC75iEFGgg2aj+lailhvWiUV0Vh89/1Mv+cx9NmLGWJBk1SguBbFQgYbm3DIxDQETA5518EJmxh1Kczaapak0yoSmdTCxCJx+koWCzO9dISgVF9Dl6nRtM66bLzFVupn5EYbQ7OUpOlk3RKlqE7IDJGQJuAl4R8KJchUJXBSDGfbJVKGgSbpo2UrYc4istNn8fErr/HZa68xMmpQlYyysJQjU5Nk/b33YrtDDoGNR6soZri+EUdR/wIrX+rNfdchJLnNgOtYBumauYgKSCVw5Ipq9MOq475YOKeceD6vvRJo0pZBcR6adg/xeIwPPniZbC4owRW25nPPvcIpp5zCdtttz0MP3UxnJ5x80im88867SiuOHj2G7XfYkcGDhzBt2kM0NQXBpIkTb+eyyy7jzDPPZPz44zlv/A1KI//lL3/hiCP247LLbuOZZ55R1NNHHp3KshXzWdG+gF8c/jN0QzGeVQGDPHT3Sp/6Oo1rrn6Ch6c9Qld3O2eceRLHnbAfr746nXFHnMA6ozbg0ksvZd11G6mqgqOPPo9XX32V4447jnPOOZz99z+emTNnctNNEvjcmEWLejn88MOJJeDOe65m3fVGqkbnssHBY4+8xVlnnav81D//5Xx+cuDWrOzMUluXVL730qWdSoDU1YQVWN99Zylvv/2eEkBtrUO49dbbaG5OcPvtD3HjjTfw05/+hMuvOI3ly31+dcyR9KY7uXbylWy77YbqfUrg6RtBKp0ZSgWNyy6+mQfue4bjjjmdk0/6Ac8/v1yZKWJXt7W1qtC8bnhoukV9Y4yp999EfVOYguWpgfzxD79nlx22o7cT/njuJTw49Tm23mpnetO9pDMd+EaG3ffakQkTfqdazb70/BxOP30CkcQIrFKImKrmKGH7aaqqRZNOVimYYJc12aBJoyjmpCb1lJASsno2g9vXS0SCDM+9zt+uvZnchzOpL9lUaz4NiTh2Ka82aQp2KjTUgu9zLXrcEj2egx1PYAvAJETuaejRGIm2QdSMXp/YqGEM334bUsOHQsugYEMnQYvMbMhE3OAeldSRhmgeUbX3RVksquoO5QgGJP4lK+h77W0+fOxZlr73MdGiTXUsguMX6NNs8nUptvn5gWxw5C+gVfiQGpZo5khU4V5tW2PL3jkmvpj9ksZR6b2vAqlqpaYknOs6mEaYfN4hIn2HfTh63Pm89MI/ld82btzPOPW0X9PXl2brbbbiuuvOVym2REJA+ibnnnsu66+/Pg89dKuSCSec8Dte+furTJw4iSFDhnLqaWcQj8eZMmUKQ4cmlKl81lmTeOKJJzjnnHM466yD+fTTNPvvfwDbbbs9Rx45jlmzPlNpj3vvu4szz/o1RSvN3j/ajVHrtCqzUgJLluWqPXPE5Rbm6KOP/IPTTjud+voa7rv/bpqaa1m6dDlHjzsRq+Rx6623ssMOQ9XYDznkBGbNmsWpp57KUUf9nGOOOY33339f+aYbb1zHP//5hRIgyZTJnfdcw7DhbcrTkZDEnNnL+dXRx9PV1ckFF47nZwftI7USpPvs8k54sk0nvPD8P5l87Y1ss/WObLjBxkoISbT6qaem0dwMkybdwx133KawccWVp7N4ic1hhx2sQHrTzX9l552lhWvZ3P0mTZotLPMT0TqOP+4cYuFmrrj0T8yaAVdfeSvPPvMigwa1qtxVXX2S3vQKYgmfY447jIMO3gcpYGlf2YXj2LS1tihzoacLZn26mCN+8WsGtYwgZEZZsPALWgc3Kv926n03KkU0a9ZKTj91AgvnGYwcvinz5s4kmTKIV3kkUx633nYFQ4ZqgTkgWyEK6yfYcEKlFdQONBXqTKEEfUX4fAFzHnyMNx96VPl76zY0UejpUYR6PWKo/VJFt8qWilTHMerqKEarqB88jJEjR9EydDB1or4HNQUBpUQ02B1NVorqJ1S2TZQzIUwmjS7fUimDpHQJFDSJ/S/+ZiYLuTzMmcvc1/7BzFf+QXFJOzWuRpX4qyWbdFjnbSfD5j/bnx/+/GDMMWNAos6SrpHWLJFQmcRfyahVtpgot3mRCTGCbvargkeryOgVCkOw4CWNEY+ZWEXRhufx4guvsuuuu3L7HRdwzTX38Yc//IHNN9+cCRMmsNtuY5VP9sgjryqQin93zz2TlSY55JBTmTt3rvLp9ttvf/bb7wC6u3t46aUXaWqKqOjmsceeowBx6623sO++O6t7nXXWRF5++e9ss822XHjBRHp7ezn2uKMxQw5nn3MaO+60HU3N1er6UsmlUChQUyNtUIP0xr1Tn2L8+PNIJpNcfvnl7Lnn1rS3W/z0gMNYvmwlzz//PKlUTAHkoIPO4LnnnuOhhx5i773HsOWWB6horqRVtt56OPPmZfj5z39OY3M1Dz18B3X1QaRasiEShjj6qJN4441/8utf/5o//OHXylMRHdLR4XDrLbdzxBHj2HOPH6rtRd785/OqTPioo45RzyTaerPNG5gw4WYefPB+fvYzCXadqsZ65LjDse0iF048n+2331z5o99Kk3p+2nccnWeeep2tNt+DxroQp59yO3978W1qUs0q6Ttq1HBWdi3mkxnv8MfxZ/Db3x/AkqU29Q0hFRAS6fnJ9AWsv95wFSxatkTSM79lyeKVRIWs4EJVKs6K9sVccdVE9vnxCKVcLpwwhXunvMnI4WNJ93WQTOnkiiuIJ13uufdGBg+VXcjK5kC5G8QqCpvQ/TSNvGPhFUuqA4Qu6Y7OXryPPmHOP95mxWezGdnSglMqYgkJoSpGsrmeGgkUNDeipIwUhqdqg13QhFgrPUTlR7HhK6ZI2bSsMFFUt/pyaY1oShXvlwhxUCPqzJjFwvc/pHPufBZ/OhO9UFQtP6XtiWNJxVCJUMik2FjHdmefSmzD9WkYsQ6kaoKcioBUOtmLmauEU5nKGPTACMj+FWqfaqwfMFgCZuBqNouQNwqFEuFQDFPu5cG8uZ384ffn8cEHH7PD9jty9TWXqOH/6U8TuOuuuxQgxb/baqsR3H77EypKu9tuu3HnnTcrkB588NG899576vNzzjmKK664R5mxV155BYcdtjdffNHDuHFHMmTIYGXyNjcnle91/fUPcOaZZ7HXXnvxwAN3KK189NEn8dLfnuGvN0zmoIP2U0Co7Ncq6ZpQKBAwcjz00DQmTryIeCzBn/98MXvvvQvdXTZ33H4vl15yBZMmTeKEE/bn00+7lIWw6aabqudIJnXGjTuR6dOnK/N3l112UemYM844g3yhj9vuuI4f7fMDNeWyjuXn8ceeY/Lkv6q5e/KJp2huDqmCqWnTXubOO+/i+OOP58orrsK2XaZOvZeOjpWceeYZZDJ93HTTjeyy6ygmTLiRa6+9mkMPO4Qbb7yAjg6XHXfageXLlyrhdfDBe6tnrfi7/8or1Xy1pTR8MS9LLJzkpednMGH85bh2lGFD11Od4vsyPSqftPW2Y5k46QCWLoeLL7mU4084mi22bGTZMo9Jky7m5wcdxjZbj0CIHJMmvsSD9z+mQJpK1ShFtLJzGccc90sOP2Jr1WTshec6OfPUi4Kkv9VHPKmxomMuza0JHn18Ck3laHJQzleu4St3IxRbQfagySvdKiVrDmH5kZUqM92XQZGKJSEl2kl+ZJVWMs+ySkQ9R2vAkICPAFF6Z0rotAwUWfSVHkKVtS8zK/1YRLxKb9CFS2DufL74+BOWzvyc3OLleOkMWq6A2q5O2pqGDAoRjbTm0BeG2hGD2XbPH7DhbrvAhhtBOLp613Dh6ZmmAqewonw9IOIrECrymnQMFJBWNjD+VyAVLVTeX8YDSbR/8P50brttCl/MX6S6Hp508glsuulYpYnEZJXdxw888EC22morXn/9dfV5Y2OjitiKWSuEAjEld999dw477BcMHTpMga+mplqlG2bPnq3+ftZZZ7LZZqMVgUG007JlKxRot9lmG4466uAADI+/wAsvPstpp53C2LGj1JQKzTKRWLPbwUcffcq0aQ/zxBNPEQ5F+NnPfq7MZ1EKXV0W540/X0Vv99lnHz788ENmzJihIrtjx66rhMH119/G5MmTqa+vVyAdNGiQGmM0ZnLgQfuoAFYQ23OIx00FyAceeJh3332PutoGRaRxHV+Bcdiw4YwbdxTXX/9X7p16H9tssx2bb74FH330IZ9//plyGSQgN336R8ydO5uRo0bwy1/+QpV3/vWG61VQ7LDDDmXHHXegobGWmtoK++jrYar1dnl+NKLx+KNv8torHzBj+iKWLemjuXE4mb4S8XiS5cuXMKitnrbBDYwc1caSZfN56+3X2X77rXHcEk1NTTz++JNsMnZz6uuaicdqWLqkkyWLOjDNqKpDlQS1sDuGDmtmxKg2ojGddG+GubOXKYnlU1I+wrIVcxg+spH7H7xVpXpkf6TAsf5yMzQBaUbi5ipX6OG7tooyG5pHxNCImga6EzS5zq1YydwZs2hftJREOMawtiG01jVRSueIGCa6RGIl6Sa/FVFAto+wcbNZ7GJJ5TwzvWky3b10r+ykp7sbrTuN+9l84tmC0uaaZWPKVhiaofwqz9TxE1GW5HrJJEwGbz6GTffejcHbbg5tLcqcVT6u6hsl4IwEP+JnrjLvRQgFmlTKz9VPpYVLuePE12pS8fKLliIRiDaNRKSbg8guSxXwJxLS/8knkdQUQOSxhSEjZpuYleI3NjbW0NHRQ01NDbGYRjYrqYiCykXW1Qq9L/AE3nnnUxW/kBzn0KFDaWhIrAqICButqSnF8uW91NXVqO8R2pzkTCVF0tZWq2RnSRqKG8IIMpSWElqhgLmrq5tQKKzMXznSvSUVQa+tDf5fNPw778yiu7tbkSmGDB5Cbd1qoAvo3nnnIzXm9TdYn3g8ou4r14k2rRaz2vdViqlKNtSSlulFT+V7P/pwOrpuUFVVrcYwfNgI6upMVq60mDNnrgLvZpttrAyruXPnK6rl8OFtal5EvBaLBWU1CbNINhWTdjC2CG98NbffFDRSboxT9IX6yqSJf+XxR18kFmkkFqnD1BNksyUS8aRqN5JOd5Hu68Rx8zQ01pCqjivVLZsMDxkyhHy+QDaTV1JHHHnhrFYl6zD0qDKZJSQu4etCMUOxFPxI+ZsUkxeLeeIJMZ0Nliydw6h1B3H7lOuJJwziSdUde3WD7v6dBCT9U+ZHS5uVYCl7lChScooqCipBprpIEiHads35gjnvf8K8T2Yx/9PP6f5iPsMTYRK+S8gwCcnmwNIhX/Y3LRSx88Vgs17Z98VxVSd91QBckdU9QrZDOFskGY5gxCJqL5yMZ5GVUoFoGCcRpWbEENbZcjM23XVntHVHBY6P8PkkMiMmh1tuwqSLcIjga2ZgSeuSVpFXGaRR5JDvVRa4AmmZEF7Z/aO889xqc3e1T+o4EkAS7Af0tFg0MNElghuYW57SoLLA5JAAiJQjVvi1AlYhksiPgFfGJWkd2XVv6ZJlDB8+RN1XzpNezWJar+zsUhFjIRkEddvaKtknIHWkK7/aJjMIUgu3NRIJxiWni1CXe4mwU4VDKoAkW2AGjKVK//JFi9oZPGLsYfQAAB8pSURBVLhZCYp83lW0v1hMV/9WrKK+PgYNqlPXiSBQPcxlZ/lIZX59FYGXdJk8twBS5kKshlWH5L7L3o181tOTIZWqUq+yMjb53ddnEYmEAz+z/FzyubCqFOmkwrFWfwv2SZI5/KZDc63A3L3wgpu55aa7iUXriYRSSHZDuIwiXYR+tbJzheJyRqMmCxeJxAirl1ZbW6vYHMJDFFtfIn5F2SG76Ciw+p5sRRhTUkwWgTBghIgtTbdlm8Kenm5lCkRjBq6fZ2XnYjbceCRT7ryJkaOay0ERtbNuuVqh7CiWq6CK2SDq5uoeJc/CMTy1p6eheutqFEpZqswIEQnpF4RMaUO2iN3RQ7F9GR+++DilrhW0L1tOz8pOrGweXRpXy+7b6NQmqvBLtgKt2otGcpWySbJhKEJBQvZzCRv4AspYGLOhhqb1RzFss7HUCCjXW6fclsFUkVrFIxYT1LIpWgWiNULiEG0p0e1QuRSuUldR8UX7m7vlBuSrQFrZWT3YHnJNkIoklwUYWbW7uBDIY7FoIAAqPlFZCOTyQmUTzm1cca+VRikVicq41abNElEuA9y2lOkpRyaTpaqqCsuyyhQ5X2k1IeoHWzTqLF++nJaWQWqtCNDle8S8lL5ZAgzT1FVcTjSkaB1X+gsbhgKrACjY1DoopYqERYAExo5cI6auEOyFA6zrmrqHgLVCupfPBSS5XGABiDAR4RH83cNxA60u45R7yXjkEItBtKf8TYAm18qPtBASQSH3i0VjahwCfiWMNAFrX/n+8hzOqoZwolXl/vFETCkEx3VV5P2b1KnW0d7ty2Q+8fiLPPLwU3hOiHisGs+VhRhm+fIVrCfMnEKeTCattKp8sQBOWqmInS4PIWaMaEThnabTfaqKQaoXlIQMB9JUJlxyrabswqRJ9UGRxobmoBzMsygU05TsDM0tdZx19ikMHtJS3rJTxFilpKf8u1yWKZ0GlZRSNZ+Ba6e2shdtZpWIyAtWeVOVhg3KxCTBKwEJWxqNFcHOCykXMjmsrh5621fSvaKDYm+G3o5OxRkW9WZIv9tIRL0A+THCOvH6BIm6FHGJbtfXBdpRIsISLRFAyrXyW/wa28OypQJGtHZEBcWKZFWVlE4ELWD0rhKsImmDesk1A0cyF2pX81XlLwE4A5BWIkpB1FcsGEUKKNdMSqtWeW9iBkdjEVVoLzumh9XOcYGJpjaPtqViRPLjQbMvAY0IZTnPcR11X9G2wgJavGSxMjHlcEU7lBd5e0e7Mn/lOjFrG+oDwrste/WIJSJzoEu3i0BTyvcq3m5K6GkVbeOtAn9F61T8RzEjhWAj23OKYJHx9D+EIig0QHkW+ZuMWbRzxUIo6zslJOW7Q6b0mV4tiPrfS55LBIwcMi/y3JU5lc8r8yFjF86uzJmAXARiQcoVdREugVCTShgRQMI9CLoNfkM9qe8LkU0nm5VmYzGi4g9YgWkg60wuX7HCoqk5rEwKofUJOVrUv4St1dYkQjbusWlqCnY8UyHrcpWB5PKk8YBy82T372jwdyEXKzOnFPgG6kVJ9ZYpNMQ+GhtTZHNSKSDk5i9rUXnYypoUSSnLy1CRVpGMwf40MhHKTFNaN2jFKQtbip9V5xEVUykGJqecpOhT5S7alb1GyztvB05GObxb2XFcdoyS9a92NQ52G1eifRWNRCVnVUxW9YNQVRem2ncm8MFyRBKV3bqDbnmejE3t4F1ufyJtM/qZ+8HZkoQK6kIrmm3N6G65/lXtkC4VMDo9vb2KNaZrorVC5HJ5Zf3IvcVlEUErC1UWl5AzBEiV36sLyn31WUWrBto4uL8sbgGDaD25d0UbywKVzwS4mWyf+h4BjOzGLkUTYoKLRhIzUappJJ0n54hWVlonHi+bhp4CgizsYEvO4J0LSGWja5mTioBI96UD0JriB0p5nFTbRBVoEnFp9xPMi4xJtLOATj2TFEYI2UXWjxTqh8OrwKbGpyiY4mIFfnksJqkbMdMDS0PAGotHlUCqleRqv0N2J6wIugpYM9msYrF9I0gdv1v2+VOLQ5m3eal3SylTRCSVHL290tazag1QynqUIIQkyVMpk/aObqUBZeJErat9ZDzIZYuK+f9/2rsWsKrKdP1uEJCrCN7mqHlMwyvaxTyjYtaUqaGCl6gEFEQtm9JSMQHFtLGyFDW1VBS8jXlJajIv4e1U4i1z9Jg1OpqO5o0QUNjcNrDP835rLfaFvTeo5zyTuvfz9MwUa6/1r3//7/9d3/cvKCiDr6+70v2vbh5cZCzPFBaICqeMNS+f/ZFeKtgZIzCWUTOURp2cQE4dYO3cIoqNERNsvXNnrCJZWkVJT+dCUrYB7nRJoEMuG979fMWgSj+r6BRUorSkEO4urvB28xCWjY67e6GeeqcK8LgDcjcRIWr6u67KP+RPGkugcyF0XCSx5MsyCiFGsNNaM3NbUYlyehAUG6/6E7mFJfCtSzK6QXiVXnV94SoHRylVGGVPYEzKMVVVQxUCt1ofYjuiNDUotqeau2upWmDyQrSFcWdCXtozzZdjDZV5C5UGc+/IfPwW69vqX6xJ0tVY546+bONv1kqA1vezJq6b/71mwrbpgdbf08xMTfMlazvPWE60UFPWxV0ASVIzQab43jTLDLZLJMhlSYZUpbIyukgKiK9fv4nABn4ipE2NIwo2K7sNCb6+0BcWy+5NF1EBMF2mUhFmdnOtK0BlVrFQzyA/UO2TvIF69fyQnZ0tFoAMB+2ZEou40Hrr4e3PLKIRbi46FBUWynmpXh4e0nxex43KhpJpUNxNVmcYa9CK0b0hNclQAk/u9BIRKk3xTBLJxkBmAD9EgZz4rQC7lHEG2wzF2riiRK9HJZX6ffxQx8UFN3Nvoo5LHXj5eoGOioExk6cbSkh65i7l6gJvH9LI+d75kmCpo/OQmiBdYvrt3NHJvyU4STCg9q9pGZuU9USJxeJ3NgFVyQpbA8n8Ytqf2kpS2lv7t6YyYF7HtW1BzO9n/UxboKh5kdtHraZTbP/dFC/CHGCW72sSI7d9D9tCbrc2Zl2lscDI3k4ClP4xgUXSqnZzcz+e8SeTSByzBhR6Bnn5N9GggZ+4M8yUMTCmSReiNpi2V4J/BuqKO0MLVyRHDxbrjdJhEhCgpL65UDX2uuKKMOvGBgCDMOlJLKaV9/GmK6K40Byjtxctd6Wc70ITL3EBtY0qKiSx4ckjET3rgt6j5G2KjXD3UrOLlRWo5FETdN0o1VlaBlcPD5Tp9XBnQ36RHjo3V+hLS+Du5YnSCgN8vP1QXKiHv4u30NvKCil8xkSBEvKyosKGI4YMDB+8GJayFldshBv1k4rLUFZeAndfZnVdZC5EDpgbkHXCj0iUc2CVNIxmRaX8QZhV+82VhSQW2KFdUdz/mtwt+7ewtjLa00xPtW2prRe9+RNuBaTmYuC3aEC1yx0ZYrNsrOXdTV+yD9IagKgmmWrjyejKDIVGLkyCVK8vgZcnAQocOvR3ydyy2yg3N19qOpcuZUsGNyiojejraGIDjKePHv0ePZ94XHkXWqwKFrCvyXc7dWqD4uJKSY3zb0weGcqLJTtYWekmsRUTGkVFepw+fUosLvmLLNnQZT558mcEB7eT2Pann35GQP0AXLuWjS6PBSt9DrTERcXwqOuB7N+uorzSgKbNmqpuMlBcVARPL2+57tjR42jR/D+FVsSyS9OWTVCQUwDfBr5yJEVlRQVcvFxx/VIOfP384ELVgbp0b4GLl36DB6VEjJXw9PHG5fMXEeDiDTfUwdXrvyGoY5Cc+fRbXh48/Xxw5do1kR7x8fREPW8vdiJCn2eAj58brv/rNwQ2bUilNTk2kTRwVzcdzpz6FUX6InR6NEikgauSf3JQs3JwsMS4almDAK0OUsV6agkMS0tgvty0YyZuc4FXs9Lqj+/wdpYAtW2pHN3A0hNQNpk7/NgDql2Qmp5nX8HfzrisvN5agbSiolyVanUVKo5/PR+cOHFW+Hmsf6akzBJwsGx0+XI+hg+PlraoiIgBkkSiJWNP5IWL5zFnzgdiKf39PYVBsWjRx2gT1A7z58+Qa6kJJgA28mBilk6Yd3VHYaEB06ZNlW4VyngwKGeDxIIFC6Q7hF0ghw8fx9Skabh8+YokDpgY6Nb1jxgxLBrtO7fCzTyenH0DKR+loFtIN/QPGyDuaR11Jyk1VGD7tu2YmpSMzsEP49rVaygrMsDPox6C23VCcvI06RIkbY+WbNy4qfj+yBGsWfdXBLUNxLnzhZj/0QJ8tz9LLHfjJk1QWWGAoVQPF50Rbdq1xbTpM3Dl6jUkTZtWlWNiMiWwfn20ad0aUxOnwNcLOH/2Cqa8NRlPPBGCsa+NlrQ+rXtBfhlmvz8Hp06dRnz8RDz8SLBy2pxWWhFLqgBBLKrq6tr7oU0gVUBb/XMnVtTaFNlelFquTbm6ugWtzSK1D8H/i/HfGcBrd8yG/Y2kNu+vM1Ibu0JR4ibgmMVeMH8lVqxIE4u2du0aPPRQI1koubmlSEpKxIEDB7Bv3z4pPp89cwURES9g4sQJiI4Ol8QQm3ZSUzciNXWFxLpkKLRt11Q5xlNqcyxDlIuIU35uCVJSPpKGbIJ/6JChEoeSoNu4cWNMnjwJR478XZqdH374ESQkJEjdj61fiVMS0aNbT7z//mz4B7ji8rUCdA/phukzkhE9IgJFpcyolcPbi+UNYNuO3Zjx9jtyfaOGjeDh6ol9u/Zj65fb0L17d4wZMxK+fsC3+04iKSkJ3j4+6D9wAF5++QVJdp04cUU2EQIvMzMT33y3Fx/Mfxc3CvKknhbSvSuy9h1FYkIi/vzq6+j2x27IvZ6LQwcP4rNNGzFkyGC8+mqMeAeDh7yAoUPCMWL4SzJf/Hyz9zDmfDgfFy5cRFxcLMa9OVLrrDcBVXNV1LVlUYWxWm/WpQbby/FOLZG1nKUja+rIzdVGV9N4rDebmq6/MxBaftv6WfRWapO4sr+Z1BKkFWyeURvOFBWRuJFj8cgjj+LEiR/x2GOPIDLyJXh6sT7IQm0punfvhslvxSMq8iUkTJmJX345j9TUpfD2ZqKG15QIKbhD+2AcPnwEbdoEITFprGpFCVJ2hhgkBb9pw1akLksXNn1YWB9lv1U50triDQ+PlPQ1icWBgUy/K9ds2boHSdPfwfg3JiA2doD0XHbq8Bjmfjgbgwc9Ix6AZNfV+325ZTumJU8XtYCmzRoruSAjMDXxXfxw9CjWfboODRq6Y9asRTh1+jSCg4Pxr/P0EGbJtcV6pQTK8HFRygp8u/9brNqwCm6KBI7QnXbu2IeZb7+DVSvXoM1DjYQQwxzU+x8sxcFDB7Fw8UL4+vtgWGQkBg8Ow8ujIpRYtBJ479154s0E1Kfl/gULF86RkpUse3MNZ+t1YZ2g1ABsc/3YcjdvdyHzXrbkLG0tSvNY0/z/mw++ekxrOTLrGNjamt/6e2i+ib1vKsdI2BuXvfFYbziONpKaNxmdwWAw0u2k0BPrhxcuXMXzQ18U/aJVq9Zg585MbNy4RtxdklTZKrh9+zbMnz9POIWTJr2F1avWoHPn1rLQ5MSECzcwcEAYduzIFHrSypVp+GrrBqVUwtICgz+dcq7M6LhxOPPP89i792v5viaByPtQJeDXXy8jIiICsbGxGDs2Tv7O3k5+eMj2wMGj4R/QCClzZwng+vcLxcQ3xuO5fs9C2jDJw1RFn3bv3o/4t97Cjq8zERDoqbaasdF7GxZ/vBir16xGXU9P4Rr269dP9GtiRozA19spieGnnFFaooBx1juzcO7COSxfuxyl5Szyu0gL8cGsE/jz2Nfx2abNaPVgINgkwzaxD+esxuq/rsXxE5mUNcKgwS/ghReGYMzICCVZ5AJERY7Bc88NQNfH/wvRw6OwenU62rZpWgVQ9SQJk+dYw5q2vclbIrc2O7n9pW/KJCsL2XxA1a2Oyd3Vvqd9xzLOdAw1W9a4NtbM9l3vCpDqCw1GxnxcKLSiS5csF+uXnrYEZ85cFuJsSkoKOnZsocgy1iVwCkVVjuz32NgYxMe/KllNWi3qvyxZsgb79x9AevrHyMo6igUL5iF5ehIe79pWohKt5k+3LmbEKDz11DOYNi0excVGeLgrh/7yXnzegQPf4/nnh8qmQUmLwMD6ipRkCZux62D69AU4ceIU0tM+lnbYxx7rhcnxExATE4YivXIfrWfzq6/2IiEhCQcO7ocfT7copnxjEV5/fZx0hqSnL8LlK9TEGS6Mja5dg/D004MQGhqKiRNGITfXgMBAN6ljJifPxi/nzmDZimWigVNwUzmb5fixU5jwxiT85S/v4cleHSUW/+GHf2D8m+PRv38o3ptNln4lXh77Mp55+kmMez1Smjt+/TUPsTFxmDIlEf36dcGf/hSB3r2fwcRJY+R9NVqT5Hbr6FDOBgT12MPbz87euuWxbdms72PPOjjI0NzWUG4fnLf1uGq58to+v2Zr6Wg8OmMFo1JaSLaguWLsK6+hefMWmDUrXpoMQkOHYNSoUXj++X5STqBANhf+uHEJ+OJvGeLm9u79pFhasuJpvbp2fU6Eq4YPf06eHR4eiyefCsHLr8SJy1xSqpRh2FESEvIkRgyPlXiU6g/NmzdU5YDYwuWBzz//HIsXL5LEDmlG7jyfs4TN4GxZIjn9r9i2dRc2b04X4YS+fYciblQMwsP7y7PYICNapwZg//6jiIkZiT59+kqbYYMGDXHkyFHpJZ0wYQK6dQvCnDlpQtH68st05OYasXJVumwQmzatlvenXCO1ZdPS1mL3nl3Cvqdlp0Viw/V33/6MMWPGotWDQVLOup6TKzXe7j26YeLE8Qp1q8iAmJjhePHFCMTEDpJ4d+aMRTh+/H+wefMyoXYtXpyOI0cOIy39E9l86MWI6ytMvHJp5eOH55/++0B6e0vd+a1bmwFdfl6RkW1N7m46ZGffRK9eT6FnSC+0aNFSyhxffP43YeDPnfsXAQ81ZPbsOYo5cz8UKcSSUr2QWP182QxNsBvQsUMwBg8eIg0RtFBbt22RUs76DatloSmqnOXIvZ6H8LAI9O0birffnlTFgKdVJlPBy0uHgwePISoqEkuXLcUzz4RUdSxdvZoLH+/6mDH9Qxw/dhJfblklOq+RkS/ijTfH4YlePeDv7ypiVSQec2xZWQcw5S1aqlBpCKclJsunf/8BaN7cC/n5QGRUNM6fP4fIyEiRFGGL1+7du4RX2aBBgFg1gua99xYhc+fX2Lt3S5W6Hi3z119TczgNgwYNlVrqxo2fyS+yfPkiBAaqXVV5VDh4CUOGDsaYMc/LxhcTE4OzZ88hPGyQaLZeuXIZP/18EuvWrUXbts1QVGwQ/iM3HZav2GSu9JmyTc9OUHpra8F59e90BnRF+jKjp6dShF+6JA1Ll6aKtWF8ShVA/rNhwwYhBAcFtZSYc+jQKLRq9SCSkhIQ0rO7ZFwZN9KSrFixHl988Tc8+2wfYRuQO8f+Wx4LQEnDP/xHI/j6spNGaRlMmDJDiMirVq1Ey5YNkZ2tR5MmCheRVCpa+Y4dH8GkSRMxalQUrl3LR6NG/lX9vk8+MRiPPvo4UlIS8M9/5mDI0EGYPftdPPWnnmrDudJQUFBQgV27dktmd/2nG9CsWQPk5ZEvySZytj5S4Pm0ZKlJHmZzNmUuL168iG+++W/ExcVh0KBBsknxM/OdFPz0049IXb5EGi747pzDHdu/w4IFiwSoLVp4Y8f2Y5g/fz5GjY4TUnRgoLc0ZAweEo5hw15EXNwwHDlyAjNmzESPHiHC5KCEJlkkGRmbJcsbO/IlRTXAWAZ3DxcBqWddD7VXVVEodH7u3RnQsUxK63D1ag6GDYtC7959RBGO7iSzqwU3gS5dHsfUqVMRFRWGhQtXYtOmjVizdjUeeKABPvkkDUuWLMHmzRlo3boZQkMj0Cm4M2bPTqqyer/lFCM8fCDCwvtj8uTxIjJMd5fuasbmTMyd8xEmT45H//69qo4EOHPmmlB+Hn74ISQnvy8JrNTlqejcqZXaEAHMS0nFunUbkTxtOsLCQ5CXV4l+/fogMWkKQkOfrlJXz8+nXo4nvvv2CKKjh4vaXcOG3gJOZoCZDPPz88CaNRuxfv16bNmSIW6pJm8ZHz9FAJyZmSFgyc0tlk3r0KEDWL0mTXqcpb3YCOzbdxQjho8U/dXAgIYIDKyDqKjXRFJyy1fr5b58bo8efTEpfiIGDuwtygEZGZ9j586vJDdALmT9+q4YNz5J2P1pacvR5A/1JC4lK4btlwonswIe7syOOUF670IU0JWWlklQSmXyMaNfwccff4IePbpKDEdLxlgzJubPQiFiEmnmzJmiPD5jxhTk5uqFeRIWFiaiVk/07IVx497AwoWL0KvXf4n7SqDn5BQiIXEyLl78FzI+36SexUH3uFQU7N58Y5rUXdu3b4+wsIGiDJCVlSVtfsuWLZKMc2JigtRte/fuLc0O7EyiMHdUVLRo13h5uQoNLCxsgJR8OnWmdIc7Ll36VZQCqJ6elbVf1Oqy9u1H48ZNpCbMeJDxuI+PK3r2fBbt2rXDsmUL5N1JTKbLvXnzNolZKWzVpUuw/G3evAXYnLEJ33+/T7wC1pgLC4tx6OD3eO218di2dQdat1aONdiz5wcR+urbtw+mT58gyamnn34KY8e+gtGjRyE8PFzkMT/4YJaM59y5bLRs2Qjbt1OWMg7rPl2LkJA/KmLK0nlUjspKtj4yJmW3gxOk9zRIjcZKI3thmandtWsPhkfHyKE0pASxyZwtZz/++A8RM2YHEpX/CNYHH2wmlpJWODNzl6iztWnTVtTv56W8hxs3ylCvnrssaJJIsrIO44svMjAyboScFM6Tr6Q04uKK6zkG0WHlGCilyGaGkJAQqZ02buwnC/fs2WwkTU2SGJHjIs2Kbik3CH//OhJzUr5jbsoc5ORki7UiMZf/rUuXLgLSs2d/kaaJ5OTpCAwk08d00M7Jk6cxb948UcHr0KGjJLV8fAjyHImtqVDHhoc+fZ4V13bLlp04dPgA3pzwmhABSK8iUI8dO4HPPsvAsJei0Lp1K5kfWs/U1HXYs2c3Pv10hWxa9EQ6d+6EDh3aiyhWfPxktG3bTqiCGnmAm9TbbycjOjoKHYPby4ldbLgX0SyUw0WlBThBei9DFNAVlxQa63p44ty582jZ8kHlbY2KrCKPdNMU1FQqnbDeeSoVm989vUg9I59QV8WaZ3ybl0f2vbeAS69XztEkp4+WQDkazogbN/PhXy8QhQVl0ixPwNDtZCM+lRto5TRaW06OXtxTWo78/FJpbAikDCNlbc0sIRPimgyHxk/l61AjJyAgoEqNgC4lGT4K015RHOD4eDwfgUavgfQ63ovZZJ7GRQ4kx0VtHoLHz68uCgqK4Uul+9ISyYzzGipSKCoCiqgV5y8/X19FILhxoxg+Pp5V0pXMDOfl3ZTjC/i+ly5dxgMPKIceGQwkYDNhpJAJFO5nibCPqCZAnSISxZ0gvcdBWmksU4s9mliOGXXJqgwkC8Wq5FOtYO6wFGZW/K4icpsd3nobc21djNckLCxvZToJW/vvpnHXos3MQTlMqGTSdWOaN7l3TSU0dR6rNROYf89irjXFBU2YjBeyH8YJ0ttYNnfVV3SVoszAj4pAc6Ev1arKXy3CHtMqVBZ7bRqdzVuoVO0TyoBYtNHc5txpt1Z1e6rcAbPbWfL6zHsubaGihg4Ys+cpx7ErgDGfh5pbOpWb2OYbWs+DthEo8650yWggVZQfnJ97dwZsgNR8sSmL1XK3N2noCBvDJh/RVktYlQ0zMzOahbNlzWx1adTU26mpGVQ3Y5bvYAJpdZBYPtcSbNXHpFOFqS02OvVVqwPVfFy3ClJr86xtDLU48efeXb/3xZvVAFLrnd5yR1dAeqvMfquFKgph9q2ZZYOzZrW136Y6aO2xEkxKeop7YCJ9ObJCfD8NDNbrQdvAHLvL9lkSCuhqsqTa96v32GrjutX5vy/W9T31krcAUm0nN/9fRyA18wntThnpNyLfp15RHXSW8h6OrKvq5FZhRrtWs1ia7678e+1AatIes9d6V1uQ2Z4C86Mh7F2hjr+aS1vdxb6nVqbzZapm4I5AKstHPW6++pzWBqSMTdmDasuSKlbTBNLaNG2ba9aYX2/jnJQq+rRjS0TitH2qkurkOqCSOOYbclzWbqz5TNqKja3n4c6at51Y+P3PgM6U3VVdSSv31WQpLC2pInLFTI29mMgeSC3Bo1jS6jGkcu/aJJYsEzemONBy8ZrcXfPkC5/iKKbTYld7Li/nTHGX7asj2Ho3bWHUFqSWHoYpWWf/ub//peccYW1nQFdp5PnR6sdCgNraSph2fO0AIQVI9ixR7UBq25KYFqU90KnOrWqJtBewZ0nNF7MKUvXGNWWXFUvq6GOKaW0BtWZLqjjftj+2EmWWcfKd8UFru0yc1/07Z0DHjiPLAThyn+xYvDt6A0eWpjaunD1X2dGgbuc7d/SSDr7s6P1Nm8//19Od9/39z4BoHP3+h+kcoXMG7t8ZcIL0/v3tnW9+l8yAE6R3yQ/lHOb9OwNOkN6/v73zze+SGXCC9C75oZzDvH9nwAnS+/e3d775XTIDTpDeJT+Uc5j37ww4QXr//vbON79LZsAJ0rvkh3IO8/6dgf8FobZih/mDGMAAAAAASUVORK5CYII=" alt="logo axa colpatria" />
                            </td>
                        </tr>
                        <tr>
                            <td align="left" valign="top">
                                <img alt="contacto axa colpatria" src="data:image/png;base64,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s3/84x9ryJAh+sUvfqEzzzxT48dfqHfeWalLLsHq7qGPPlqqHj1208RJtyu/TXDf5RUscFM0a9Ys3X77Heq0487q0GEnnTLiNA0+bH+NG/cHPffcc+rbt5c+Xv6R+vXrrfHXXav2HYh5Q+hWH7T0+v3cAWuoVJHq8nh8yJDRJhzird/+9rcW/91444169dVX7TV//r1atOhT/exnP9M999yjJUuWmJb37dtbY66+wgR1+WVXaNSoo3XuOTfos8++0J//fI/GjLnKYqsrrrxMX3+9Uvc/MEWPPf6wOu+SJxbfrD1Ihb7x+udmcf7973/r1ltvNRDOnHmnWbdrrrlNCxYssMXXlVdeqWOO2V+bN0tLly43ocx67FE9++wCzZw5U127dtGQIcdq11131Z/+9Ce1bUu8KU2f/rh+97vf6cknn1SnTp1sngB6/LjxmjTxPs19ep6emTdLX3+9TiNPG65hJx2vSy+9WC+/slDXXvsrTZ8+Q4ceso/mzl2snj/aU68uel1XXjlGEyb8Vqefcawm332veR5407//rioslE44YYTF6dOmTVPnzvn69NO1OvHEE3X55Zdr1KhTtGlTuQo2FWrFii/04IMPatWqf+uJJx9Vxx3Jykix6rCQBex5ea01+8nHLDYme/HF5xt06qmnacSI4br+hvM1cdI0TZx4h/465c864ogBhk3Ck+ycjAZVZD8M4EaqSuLxO++cqYULF6pXr162+gW0WCWsar9+/cwywMihQ083t8r3pJJWr1mlAQP66/7779ddd92tfv266qk5f9ett96ms88+R88//7w6depoC6iCwo3asHGNHnvsTyorJ96sVl5eqirKpNtvv19Lly7VTjvtpNdff1177bWXJk++3gA3derjZjkZb8GCuerYUXrzza/0l7/8xbIExH6EFniCrl276rDDDtOIESN0ww2XGOiJhUePHquvv/5aU6dOVdu2EV199e/MSr3++gu6ZcJfNWvW43rjjfkqLIrpzLNGauDAQ3TLLZdr3vx/6PzzzzcFPnvU8brxxj9p6YfL1KVLN82f/6zOOGOkxo47SxN+N0UzZswQYVb79rk25uDBJ2jffffVhAnXiXXhO+98ZWEW/Z122sl67bW3NH3ag+rcuas+/GCpNm5arwcfnGYLqdatU5SZJa1cWaSTTz5ZAwcO1ITfXmcGJyUiPfzw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" />
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        ';
    }
}
