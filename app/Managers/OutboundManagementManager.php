<?php

namespace App\Managers;

use App\Jobs\DiffusionByEmail;
use App\Jobs\DiffusionBySMS;
use App\Models\FormAnswer;
use App\Models\OutboundManagement;
use App\Models\OutboundManagementAttachment;
use App\Models\OutboundManagementTag;
use App\Models\Section;
use App\Services\NotificationsService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OutboundManagementManager
{
    /**
     * Retorna una lista de gestiones outbound.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param integer $formId
     * @param array $filterOptions
     *  - tags: lista de tags, con las que filtraran los clientes.
     *  - fromDate: fecha de inicial.
     *  - toDate: fecha final.
     * @return array
     */
    public function listManagement(int $formId, array $filterOptions = [])
    {
        try {
            $outboundManagement = OutboundManagement::formFilter($formId);
    
            if (isset($filterOptions['from_date']) && isset($filterOptions['to_date'])) {
                $outboundManagement->updatedAtBetweenFilter($filterOptions['from_date'], $filterOptions['to_date']);
            }
            if (isset($filterOptions['tags']) && count($filterOptions['tags'])) {
                $outboundManagement->join('outbound_management_tags', 'outbound_management_tags.aoutbound_management_id', 'outbound_management.id')->whereIn('outbound_management_tags.tag_id', $filterOptions['tags']);
            }

            $outboundManagement =  $outboundManagement->get([
                'outbound_management.id',
                'outbound_management.status',
                'outbound_management.name',
                'outbound_management.channel',
                'outbound_management.total'
            ]);
    
            return $outboundManagement;
        } catch (Exception $e) {
            Log::error("OutboundManagement@listManagement: {$e->getMessage()}");
            throw new Exception("Error al buscar las gestiones, por favor comuniquese con el adminstrador del sistema.");
        }
    }

    public function save(array $data, array $files = [])
    {
        if (isset($data['outbound_management_id'])) {
                try {
                    $outboundManagement = OutboundManagement::find($data['outbound_management_id']);
                    $outboundManagement->name = $data['name'];
                    $outboundManagement->settings = $data['settings'];
                    $outboundManagement->save();
                    
                    $outboundManagement->tags()->detach();
                    $outboundManagement->tags()->attach($data['tags']);
    
                } catch (Exception $e) {
                    Log::error("OutboundManagement@save: {$e->getMessage()}");
                    throw new Exception("Error al actualizar la gestión, por favor comuniquese con el adminstrador del sistema.");
                }
            } else {
                try {
                    $outboundManagement = OutboundManagement::create([
                        'form_id' => $data['form_id'],
                        'name' => $data['name'],
                        'channel' => $data['channel'],
                        'settings' => $data['settings'],
                        'status' => 0,
                    ]);
        
                    $tags = json_decode($data['tags']);
                    $outboundManagement->tags()->attach($data['tags']);
                } catch (Exception $e) {
                    Log::error("OutboundManagement@save: {$e->getMessage()}");
                    throw new Exception("Error al crear la gestión, por favor comuniquese con el adminstrador del sistema.");
                }
            }
    
            try {
                if (count($files)) {
                    foreach ($files as $file) {
                        $path = $file->store("outbound_management_attachments/$outboundManagement->id");
                        OutboundManagementAttachment::create([
                            'outbound_management_id' => $outboundManagement->id,
                            'name' => $file->getClientOriginalName(),
                            'path' => $path
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error("OutboundManagement@save: {$e->getMessage()}");
                throw new Exception("Error al guardar los archivos de la gestión, por favor comuniquese con el adminstrador del sistema.");
            }

        return $outboundManagement->load('attachments');
    }

    public function destroyAttachment($id)
    {
        try {
            $outboundManagementAttachment = OutboundManagementAttachment::find($id);
            Storage::delete($outboundManagementAttachment->path);
            OutboundManagementAttachment::destroy($outboundManagementAttachment->id);
        } catch (Exception $e) {
            Log::error("OutboundManagement@destroyAttachment: {$e->getMessage()}");
            throw new Exception("Error al eliminar archivo adjunto, por favor comuniquese con el adminstrador del sistema.");
        }
    }

    public function createDiffusion($outboundManagement)
    {
        try {
            $formAnswers = FormAnswer::formFilter($outboundManagement->form_id)
            ->join('client_tag', 'client_tag.client_new_id', 'form_answers.client_new_id')
            ->whereIn('client_tag.tag_id', $outboundManagement->tags)->get(['structure_answer']);
    
            $startDiffusionDateTime = "{$outboundManagement->settings->start_diffusion_date} {$outboundManagement->settings->start_diffusion_time}";
    
            if ($outboundManagement->channel == 'SMS') {
                $this->diffusionBySMS($formAnswers, $outboundManagement, $startDiffusionDateTime);
            }else if ($outboundManagement->channel == 'Email') {
                $this->diffusionByEmail($formAnswers, $outboundManagement, $startDiffusionDateTime);
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@createDiffusion: {$e->getMessage()}");
            throw new Exception("Error al crear la difución, por favor comuniquese con el adminstrador del sistema.");
        }
    }

    public function diffusionBySMS($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        try {
            $clients = [];
            $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
                $fields = json_decode($answer->structure_answer);
                $messageContent = $outboundManagement->settings->sms->message_content;
                foreach ($fields as $field) {
                    if ($field->id == $outboundManagement->settings->diffusion_field) {
                        $diffusion = $field->value;
                    }
                    $messageContent = str_replace("[[$field->id]]", $field->value,$messageContent);
                }
                $clients[] = [
                    'diffusion' => $diffusion,
                    'message' => $messageContent
                ];
            });
    
            $options = [
                'startHour' => $outboundManagement->settings->start_delivery_schedule_time,
                'endHour' => $outboundManagement->settings->end_delivery_schedule_time,
                'days' => $outboundManagement->settings->delivery_schedule_days
            ];
    
            dispatch((new DiffusionBySMS($clients, $options))->delay(Carbon::createFromFormat('Y-m-d H:i', "$startDiffusionDateTime")))
            ->onQueue('diffusions');
        } catch (Exception $e) {
            Log::error("OutboundManagement@diffusionBySMS: {$e->getMessage()}");
            throw new Exception("Error al crear la difución por SMS, por favor comuniquese con el adminstrador del sistema.");
        }
    }

    public function diffusionByEmail($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        try {
            $clients = [];
            $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
                $fields = json_decode($answer->structure_answer);
                $body = $outboundManagement->settings->email->body;
                $subject = $outboundManagement->settings->email->subject;
                foreach ($fields as $field) {
                    if ($field->id == $outboundManagement->settings->diffusion_field) {
                        $diffusion = $field->value;
                    }
                    $body = str_replace("[[$field->id]]", $field->value, $body);
                    $subject = str_replace("[[$field->id]]", $field->value, $subject);
                }
                $clients[] = [
                    'body' => $body,
                    'subject' => $subject,
                    'to' => $diffusion,
                    'attatchment' => [],
                    'cc' => [],
                    'cco' => [],
                ];
            });
    
            $attachments = [];
    
            $outboundManagement->attachments->each(function ($attachment) use (&$attachments) {
                $attachments[] = [
                    'name' => $attachment->name,
                    'contents' => file_get_contents(storage_path("app/$attachment->path")),
                    'filename' => $attachment->name,
                ];
            });
    
            $options = [
                'startHour' => $outboundManagement->settings->start_delivery_schedule_time,
                'endHour' => $outboundManagement->settings->end_delivery_schedule_time,
                'days' => $outboundManagement->settings->delivery_schedule_days,
                'attachments' => $attachments,
                'sender_email' => $outboundManagement->settings->email->sender_email,
                'replay_email' => $outboundManagement->settings->email->replay_email
            ];
    
            dispatch((new DiffusionByEmail($clients, $options))->delay(Carbon::createFromFormat('Y-m-d H:i', "$startDiffusionDateTime")))
            ->onQueue('diffusions');
        } catch (Exception $e) {
            Log::error("OutboundManagement@diffusionByEmail: {$e->getMessage()}");
            throw new Exception("Error al crear la difución por Email, por favor comuniquese con el adminstrador del sistema.");
        }
    }

    public function sendDiffusionBySMS(array $clients, array $options)
    {
        try {
            $notificationsService = new NotificationsService;
            
            foreach ($clients as $key => $client) {
                $now = Carbon::now('America/Bogota');
                $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($options['startHour'], 'America/Bogota'));
                $isLessThan = $now->lessThan(Carbon::createFromTimeString($options['endHour'], 'America/Bogota'));
    
                if ($isGreaterThanOrEqualTo && $isLessThan) {
                    $notificationsService->sendSMS($client['message'], [$client['diffusion']]);
                    unset($clients[$key]);
                } else {
                    $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], $now);
                    if (!is_null($nextExecution) && count($clients)) {
                        dispatch((new DiffusionBySMS($clients, $options))->delay(Carbon::createFromFormat('Y-m-d H:i', "$nextExecution")))
                        ->onQueue('diffusions');
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@sendDiffusionBySMS: {$e->getMessage()}");
            throw new Exception("Ocurrio un error al notificar por SMS, por favor comuniquese con el administrador del sistema.");
        } finally {
            dispatch((new DiffusionBySMS($clients, $options)))->onQueue('diffusions');
        }
    }

    public function sendDiffusionByEmail(array $clients, array $options)
    {
        try {
            $notificationsService = new NotificationsService;
            
            foreach ($clients as $key => $client) {
                $now = Carbon::now('America/Bogota');
                $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($options['startHour'], 'America/Bogota'));
                $isLessThan = $now->lessThan(Carbon::createFromTimeString($options['endHour'], 'America/Bogota'));
    
                if ($isGreaterThanOrEqualTo && $isLessThan) {
                    $notificationsService->sendEmail($client['body'], $client['subject'], [$client['to']], $options['attachments'],$client['cc'], $client['cco'], $options['sender_email']);
                    unset($clients[$key]);
                } else {
                    $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], $now);
                    if (!is_null($nextExecution) && count($clients)) {
                        dispatch((new DiffusionByEmail($clients, $options))->delay(Carbon::createFromFormat('Y-m-d H:i', "$nextExecution")))
                        ->onQueue('diffusions');
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@sendDiffusionByEmail: {$e->getMessage()}");
            throw new Exception("Ocurrio un error al notificar por Email, por favor comuniquese con el administrador del sistema.");
        } finally {
            dispatch((new DiffusionByEmail($clients, $options)))->onQueue('diffusions');
        }

    }

    public function showOutboundManagement($id)
    {
        try {
            $outboundManagement = OutboundManagement::find($id)->load('tags');
            
            if ($outboundManagement->channel == 'SMS') {
                $content = $outboundManagement->settings->sms->message_content;
    
                $outboundManagement->settings->sms->message_content_with_labels = $this->replaceContent($outboundManagement->form_id, $content);
    
            } else if ($outboundManagement->channel == 'Email') {
                $content = $outboundManagement->settings->email->body;
                
                $outboundManagement->settings->email->body_with_labels = $this->replaceContent($outboundManagement->form_id, $content);
    
                $outboundManagement->load('attachments');
            }
    
            return $outboundManagement;
        } catch (Exception $e) {
            Log::error("OutboundManagement@showOutboundManagement: {$e->getMessage()}");
            throw new Exception("Ocurrio un error al buscar la gestión, por favor comuniquese con el administrador del sistema.");
        }
    }

    private function replaceContent($formId, $content)
    {
        try {
            Section::formFilter($formId)->get()->each(function ($section) use (&$content) {
                $fields = json_decode($section->fields);
                foreach ($fields as $field) {
                    $content = str_replace("[[$field->id]]", $field->label, $content);
                }
            });

            return $content;
        } catch (Exception $e) {
            Log::error("OutboundManagement@replaceContent: {$e->getMessage()}");
            throw new Exception("Ocurrio un error al obtener el mensaje, por favor comuniquese con el administrador del sistema.");
        }
    }

    private function calculateNextExecution(int $numberOfClients, array $daysOfExecution, Carbon $now)
    {
        try {
            $todaysNumber = Carbon::now('America/Bogota')->dayOfWeekIso;
            $nextExecution = null;
    
            foreach ($daysOfExecution as $day) {
                if ($todaysNumber < $day) {
                    $nextExecutionDay = $day - $todaysNumber; 
                    $nextExecution = $now->addDays($nextExecutionDay);
                    break;
                }
            }
    
            if (is_null($nextExecution) && $numberOfClients) {
                $nextExecutionDay = 7 - $todaysNumber + $daysOfExecution;
                $nextExecution = $now->addDays($nextExecutionDay);
            }
    
            return $nextExecution;
        } catch (Exception $e) {
            Log::error("OutboundManagement@calculateNextExecution: {$e->getMessage()}");
            throw new Exception("Ocurrio un error al calcular la próxima ejecución, por favor comuniquese con el administrador del sistema.");
        }
    }

    public function sendTestMail(array $data, array $files)
    {
        // $this->noti
    }
}
