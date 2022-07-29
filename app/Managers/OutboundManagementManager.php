<?php

namespace App\Managers;

use App\Jobs\DiffusionByEmail;
use App\Jobs\DiffusionBySMS;
use App\Jobs\DiffusionByVoice;
use App\Jobs\DiffusionByWhatsapp;
use App\Models\Directory;
use App\Models\FormAnswer;
use App\Models\OutboundManagement;
use App\Models\OutboundManagementAttachment;
use App\Models\Product;
use App\Models\Section;
use App\Models\WhatsappAccount;
use App\Services\LeadService;
use App\Services\NotificationsService;
use App\Services\VicidialService;
use App\Services\WhatsappService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $outboundManagement = OutboundManagement::formFilter($formId);

        if (isset($filterOptions['from_date']) && isset($filterOptions['to_date'])) {
            $outboundManagement->updatedAtBetweenFilter($filterOptions['from_date'], $filterOptions['to_date']);
        }
        if (isset($filterOptions['tags']) && count($filterOptions['tags'])) {
            $outboundManagement->join('outbound_management_tags', 'outbound_management_tags.outbound_management_id', 'outbound_management.id')->whereIn('outbound_management_tags.tag_id', $filterOptions['tags']);
        }

        $outboundManagement =  $outboundManagement->get([
            'outbound_management.id',
            'outbound_management.status',
            'outbound_management.name',
            'outbound_management.channel',
            'outbound_management.total'
        ])->map(function ($outbound) {
            $outbound->tags = join(', ', $outbound->tags()->pluck('name')->toArray());
            return $outbound;
        });

        return $outboundManagement;
    }

    public function save(array $data, array $files = [])
    {
        DB::beginTransaction();
        $tags = json_decode($data['tags']);
        if (isset($data['outbound_management_id'])) {
                try {
                    $outboundManagement = OutboundManagement::find($data['outbound_management_id']);
                    $outboundManagement->name = $data['name'];
                    $outboundManagement->settings = json_decode($data['settings']);
                    $outboundManagement->save();
                    
                    $outboundManagement->tags()->detach();
                    $outboundManagement->tags()->attach($tags);
    
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error("OutboundManagement@save: {$e->getMessage()}");
                    throw new Exception("Error al actualizar la gestión, por favor comuniquese con el adminstrador del sistema.");
                }
            } else {
                try {
                    $outboundManagement = OutboundManagement::create([
                        'form_id' => $data['form_id'],
                        'name' => $data['name'],
                        'channel' => $data['channel'],
                        'settings' => json_decode($data['settings']),
                        'status' => 'Borrador',
                    ]);
        
                    $tags = json_decode($data['tags']);
                    $outboundManagement->tags()->attach($tags);
                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error("OutboundManagement@save: {$e->getMessage()}");
                    throw new Exception("Error al crear la gestión, por favor comuniquese con el adminstrador del sistema.");
                }
            }
    
            try {
                DB::beginTransaction();
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
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("OutboundManagement@save: {$e->getMessage()}");
                throw new Exception("Error al guardar los archivos de la gestión, por favor comuniquese con el adminstrador del sistema.");
            }

        return $outboundManagement->load('attachments');
    }

    public function destroyAttachment($id)
    {
        $outboundManagementAttachment = OutboundManagementAttachment::find($id);
        Storage::delete($outboundManagementAttachment->path);
        OutboundManagementAttachment::destroy($outboundManagementAttachment->id);
    }

    public function createDiffusion($outboundManagement)
    {
        $tags = $outboundManagement->tags()->pluck('tags.id')->toArray();
        $formAnswers = FormAnswer::formFilter($outboundManagement->form_id)
        ->join('client_tag', 'client_tag.client_new_id', 'form_answers.client_new_id')
        ->whereIn('client_tag.tag_id', $outboundManagement->tags)->get(['form_answers.structure_answer', 'form_answers.client_new_id']);

        if (!$formAnswers->count()) {
            $formAnswers = Directory::formFilter($outboundManagement->form_id)
            ->join('client_tag', 'client_tag.client_new_id', 'directories.client_new_id')
            ->whereIn('client_tag.tag_id', $tags)->distinct()->get(['directories.data AS structure_answer', 'directories.client_new_id']);
        }

        $startDiffusionDateTime = "{$outboundManagement->settings->start_diffusion_date} {$outboundManagement->settings->start_diffusion_time}";

        switch ($outboundManagement->channel) {
            case 'SMS':
                $this->diffusionBySMS($formAnswers, $outboundManagement, $startDiffusionDateTime);
                break;
            
            case 'Email':
                $this->diffusionByEmail($formAnswers, $outboundManagement, $startDiffusionDateTime);
                break;

            case 'Voice':
                $this->diffusionByVoice($formAnswers, $outboundManagement, $startDiffusionDateTime);
                break;
                
            case 'Whatsapp':
                $this->diffusionByWhatsapp($formAnswers, $outboundManagement, $startDiffusionDateTime);
                break;
        }
    }

    public function diffusionBySMS($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        $clients = [];
        $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
            $fields = json_decode($answer->structure_answer);
            $messageContent = $outboundManagement->settings->sms->message_content;

            foreach ($fields as $field) {
                if ($field->id == $outboundManagement->settings->diffusion_field) {
                    $destination = $field->value;
                }
                $messageContent = str_replace("[[$field->id]]", $field->value,$messageContent);
            }

            $clients[] = [
                'id' => $answer->client_new_id,
                'destination' => $destination,
                'message' => $messageContent
            ];
        });

        $outboundManagement->total = count($clients);
        $outboundManagement->save();

        $options = [
            'startHour' => $outboundManagement->settings->start_delivery_schedule_time,
            'endHour' => $outboundManagement->settings->end_delivery_schedule_time,
            'days' => $outboundManagement->settings->delivery_schedule_days
        ];

        $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], Carbon::now('America/Bogota'), $startDiffusionDateTime);

        dd($outboundManagement->id, $clients, $options);
        if (count($clients)) {
            dispatch((new DiffusionBySMS($outboundManagement->id, $clients, $options))->delay($nextExecution))
            ->onQueue('diffusions');
        }
    }

    public function diffusionByEmail($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        $clients = [];
        $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
            $fields = json_decode($answer->structure_answer);
            $body = $outboundManagement->settings->email->body;
            $subject = $outboundManagement->settings->email->subject;

            foreach ($fields as $field) {
                if ($field->id == $outboundManagement->settings->diffusion_field) {
                    $destination = $field->value;
                }
                $body = str_replace("[[$field->id]]", $field->value, $body);
                $subject = str_replace("[[$field->id]]", $field->value, $subject);
            }

            $clients[] = [
                'id' => $answer->client_new_id,
                'body' => $body,
                'subject' => $subject,
                'to' => $destination,
                'attatchment' => [],
                'cc' => [],
                'cco' => [],
            ];
        });

        $outboundManagement->total = count($clients);
        $outboundManagement->save();

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
            'replay_email' => $outboundManagement->settings->email->replay_email ?? ''
        ];

        $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], Carbon::now('America/Bogota'), $startDiffusionDateTime);
    
        if (count($clients)) {
            dispatch((new DiffusionByEmail($outboundManagement->id, $clients, $options))->delay($nextExecution))
            ->onQueue('diffusions');
        }
    }

    public function diffusionByVoice($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        $clients = [];
        $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
            $fields = json_decode($answer->structure_answer);
            foreach ($fields as $field) {
                if ($field->id == $outboundManagement->settings->diffusion_field) {
                    $clients[] = ['destination' => $field->value, 'id' => $answer->client_new_id,];
                    break;
                }
            }
        });

        $outboundManagement->total = count($clients);
        $outboundManagement->save();

        $product = Product::find($outboundManagement->settings->voice->product);

        $options = [
            'startHour' => $outboundManagement->settings->start_delivery_schedule_time,
            'endHour' => $outboundManagement->settings->end_delivery_schedule_time,
            'days' => $outboundManagement->settings->delivery_schedule_days,
            'token' => $product->token,
            'product' => $product->name,
        ];

        $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], Carbon::now('America/Bogota'), $startDiffusionDateTime);
    
        if (count($clients)) {
            dispatch((new DiffusionByVoice($outboundManagement->id, $clients, $options))->delay($nextExecution))
            ->onQueue('diffusions');
        }
    }

    public function diffusionByWhatsapp($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        $clients = [];
        $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
            $fields = json_decode($answer->structure_answer);
            $whatsappFields = $outboundManagement->settings->whatsapp->fields;
            $messageParams = [];

            foreach ($whatsappFields as $key => $wField) {
                foreach ($fields as $field) {
                    if ($field->id == $outboundManagement->settings->diffusion_field) {
                        $destination = $field->value;
                    }
                    if ($field->id == $wField) {
                        $messageParams[$key] = $field->value;
                    }
                }
            }

            $clients[] = [
                'id' => $answer->client_new_id,
                'destination' => $destination,
                'messageParams' => $messageParams
            ];
        });
        

        $outboundManagement->total = count($clients);
        $outboundManagement->save();

        $options = [
            'startHour' => $outboundManagement->settings->start_delivery_schedule_time,
            'endHour' => $outboundManagement->settings->end_delivery_schedule_time,
            'days' => $outboundManagement->settings->delivery_schedule_days,
            'whatsappAccountId' => $outboundManagement->settings->whatsapp->source,
            'templateId' => $outboundManagement->settings->whatsapp->templateId
        ];

        $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], Carbon::now('America/Bogota'), $startDiffusionDateTime);

        if (count($clients)) {
            dispatch((new DiffusionByWhatsapp($outboundManagement->id, $clients, $options))->delay($nextExecution))
            ->onQueue('diffusions');
        }
    }

    public function sendDiffusionBySMS(int $outboundManagementId, array $clients, array $options)
    {
        try {
            $notificationsService = new NotificationsService;
            $outboundManagement = OutboundManagement::find($outboundManagementId);
            
            foreach ($clients as $key => $client) {
                $now = Carbon::now('America/Bogota');
                $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($options['startHour'], 'America/Bogota'));
                $isLessThan = $now->lessThan(Carbon::createFromTimeString($options['endHour'], 'America/Bogota'));
                if ($isGreaterThanOrEqualTo && $isLessThan) {
                    $outboundManagement->status = 'En proceso...';
                    $outboundManagement->save();
                    $notificationsService->sendSMS($client['message'], [$client['destination']]);
                    $outboundManagement->clients()->attach($client['id']);
                    unset($clients[$key]);
                } else {
                    $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], $now);
                    if (!is_null($nextExecution) && count($clients)) {
                        dispatch((new DiffusionBySMS($outboundManagementId, $clients, $options))->delay($nextExecution))
                        ->onQueue('diffusions');
                    }
                    break;
                }
            }

            if (!count($clients)) {
                $outboundManagement->status = 'Entregado';
                $outboundManagement->save();
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@sendDiffusionBySMS: {$e->getMessage()}");
            dispatch(
                (new DiffusionBySMS($outboundManagementId, $clients, $options))->delay(Carbon::now()->addMinute())
            )->onQueue('diffusions');
        }
    }

    public function sendDiffusionByEmail(int $outboundManagementId, array $clients, array $options)
    {
        try {
            $notificationsService = new NotificationsService;
            $outboundManagement = OutboundManagement::find($outboundManagementId);
            
            foreach ($clients as $key => $client) {
                $now = Carbon::now('America/Bogota');
                $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($options['startHour'], 'America/Bogota'));
                $isLessThan = $now->lessThan(Carbon::createFromTimeString($options['endHour'], 'America/Bogota'));
                
                if ($isGreaterThanOrEqualTo && $isLessThan) {
                    $outboundManagement->status = 'En proceso...';
                    $outboundManagement->save();
                    $notificationsService->sendEmail($client['body'], $client['subject'], [$client['to']], $options['attachments'],$client['cc'], $client['cco'], $options['sender_email']);
                    $outboundManagement->clients()->attach($client['id']);
                    unset($clients[$key]);
                } else {
                    $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], $now);
                    if (!is_null($nextExecution) && count($clients)) {
                        dispatch((new DiffusionByEmail($outboundManagementId, $clients, $options))->delay($nextExecution))
                        ->onQueue('diffusions');
                    }
                    break;
                }
            }

            if (!count($clients)) {
                $outboundManagement->status = 'Entregado';
                $outboundManagement->save();
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@sendDiffusionByEmail: {$e->getMessage()}");
            dispatch(
                (new DiffusionByEmail($outboundManagementId, $clients, $options))->delay(Carbon::now()->addMinute())
            )->onQueue('diffusions');
        }
    }

    public function sendDiffusionByVoice(int $outboundManagementId, array $clients, array $options)
    {
        try {
            $vicidialService = new VicidialService;
            $outboundManagement = OutboundManagement::find($outboundManagementId);
            
            foreach ($clients as $key => $client) {
                $now = Carbon::now('America/Bogota');
                $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($options['startHour'], 'America/Bogota'));
                $isLessThan = $now->lessThan(Carbon::createFromTimeString($options['endHour'], 'America/Bogota'));
                
                if ($isGreaterThanOrEqualTo && $isLessThan) {
                    $outboundManagement->status = 'En proceso...';
                    $outboundManagement->save();
                    $vicidialService->sendLead([
                        'producto' => $options['product'],
                        'token' => $options['token'],
                        'telefono' => $client['destination'],
                    ]);
                    $outboundManagement->clients()->attach($client['id']);
                    unset($clients[$key]);
                } else {
                    $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], $now);
                    if (!is_null($nextExecution) && count($clients)) {
                        dispatch((new DiffusionByVoice($outboundManagementId, $clients, $options))->delay($nextExecution))
                        ->onQueue('diffusions');
                    }
                    break;
                }
            }

            if (!count($clients)) {
                $outboundManagement->status = 'Entregado';
                $outboundManagement->save();
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@sendDiffusionByEmail: {$e->getMessage()}");
            dispatch(
                (new DiffusionByVoice($outboundManagementId, $clients, $options))->delay(Carbon::now()->addMinute())
            )->onQueue('diffusions');
        }
    }

    public function sendDiffusionByWhatsapp(int $outboundManagementId, array $clients, array $options)
    {
        try {
            $outboundManagement = OutboundManagement::find($outboundManagementId);
            $whatsappAccount = WhatsappAccount::find($options['whatsappAccountId']);
            $whatsappService = new WhatsappService($whatsappAccount);
            
            foreach ($clients as $key => $client) {
                $now = Carbon::now('America/Bogota');
                $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($options['startHour'], 'America/Bogota'));
                $isLessThan = $now->lessThan(Carbon::createFromTimeString($options['endHour'], 'America/Bogota'));
                
                if ($isGreaterThanOrEqualTo && $isLessThan) {
                    $outboundManagement->status = 'En proceso...';
                    $outboundManagement->save();
                    $whatsappService->sendTemplateMenssage($client['destination'], $options['templateId'], $client['messageParams']);
                    $outboundManagement->clients()->attach($client['id']);
                    unset($clients[$key]);
                } else {
                    $nextExecution = $this->calculateNextExecution(count($clients), $options['days'], $now);
                    if (!is_null($nextExecution) && count($clients)) {
                        dispatch((new DiffusionByWhatsapp($outboundManagementId, $clients, $options))->delay($nextExecution))
                        ->onQueue('diffusions');
                    }
                    break;
                }
            }
        } catch (Exception $e) {
            Log::error("OutboundManagement@sendDiffusionByEmail: {$e->getMessage()}");
            dispatch(
                (new DiffusionByWhatsapp($outboundManagementId, $clients, $options))->delay(Carbon::now()->addMinute())
            )->onQueue('diffusions');
        }
    }

    public function showOutboundManagement($id)
    {
        $outboundManagement = OutboundManagement::find($id);
        
        if ($outboundManagement->channel == 'Email') {
            $outboundManagement->attachments = $outboundManagement->attachments()->get(['id', 'name']);
        }

        $outboundManagement->tags = $outboundManagement->tags()->pluck('tags.id');
        $outboundManagement = $outboundManagement->only('id', 'name', 'attachments', 'tags', 'settings', 'form_id', 'channel');

        return $outboundManagement;
    }

    private function calculateNextExecution(int $numberOfClients, array $daysOfExecution, Carbon $now, string $startDate = '')
    {
        if ($startDate != '') {
            $startDate = Carbon::createFromFormat('Y-m-d H:i', $startDate);
        }

        if ($startDate != '' && $startDate->lessThanOrEqualTo($now)) {
            $nextExecution = $now->addMinutes(5);
        }

        if ($startDate == '' || !$startDate->lessThanOrEqualTo($now)) {
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
        }

        return $nextExecution;
    }

    public function sendTestMail(array $data, array $files)
    {
        $attachments = [];

        foreach ($files as $key => $file) {
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'file' => $file->getContent(),
            ];
        }

        (new NotificationsService)->sendEmail($data['body'], $data['subject'], [$data['to']], $attachments, [], [], $data['sender_email']);
    }

    public function listWhatsappTemplates($whatsappAccountId)
    {
        $whatsappAccount = WhatsappAccount::find($whatsappAccountId);
        $response = (new WhatsappService($whatsappAccount))->getTemplates();
        $templates = [];
        
        if ($response->status == 'success') {
            foreach ($response->templates as $template) {
                $countFields = 0;

                do {
                    $toSearch = '/\{\{' . ($countFields + 1) . '\}\}/';
                    $found = preg_match($toSearch, $template->data);
                    if ($found) $countFields++;
                } while ($found);

                $templates[] = [
                    'id' => $template->id,
                    'template' => $template->data,
                    'number_of_fields' => $countFields
                ];
            }
        }

        return $templates;
    }
}
