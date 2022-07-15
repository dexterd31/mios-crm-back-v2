<?php

namespace App\Managers;

use App\Jobs\DiffusionByEmail;
use App\Jobs\DiffusionBySMS;
use App\Models\Channel;
use App\Models\FormAnswer;
use App\Models\OutboundManagement;
use App\Services\NotificationsService;
use Illuminate\Support\Carbon;

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
            $outboundManagement->join('outbound_management_tags', 'outbound_management_tags.aoutbound_management_id', 'outbound_management.id')->whereIn('outbound_management_tags.tag_id', $filterOptions['tags']);
        }
        
        $outboundManagement = $outboundManagement->get()->map(function ($management) {
            $management->channel = $management->channel->name;
            return $management;
        });

        return $outboundManagement;
    }

    public function storeAndUpdate(array $data)
    {
        dd($data);
        if ($data['outbound_management_id']) {
            $outboundManagement = OutboundManagement::find($data['outbound_management_id']);
            $outboundManagement->name = $data['name'];
            $outboundManagement->settings = $data['settings'];
            $outboundManagement->save();
            
            $outboundManagement->tags()->detach();
            $outboundManagement->tags()->attach($data['tags']);

            if (isset($data['file'])) {
                foreach ($data['file'] as $file) {
                    dd($file);
                    $file->store();
                }
            }

        } else {
            $channel = Channel::nameFilter($data['channel'])->first();
            $outboundManagement = OutboundManagement::create([
                'form_id' => $data['form_id'],
                'name' => $data['name'],
                'channel_id' => $channel->id,
                'settings' => $data['settings'],
            ]);

            $outboundManagement->tags()->attach($data['tags']);

            if (isset($data['file'])) {
                foreach ($data['file'] as $file) {
                    dd($file);
                    $file->store();
                }
            }

        }

        return $outboundManagement;
    }

    public function createDiffusion($outboundManagement)
    {
        $formAnswers = FormAnswer::formFilter($outboundManagement->form_id)
        ->join('client_tag', 'client_tag.client_new_id', 'form_answers.client_new_id')
        ->whereIn('client_tag.tag_id', $outboundManagement->tags)->get(['structure_answer']);

        $startDiffusionDateTime = "{$outboundManagement->settings->start_diffusion_date} {$outboundManagement->settings->start_diffusion_time}";

        if ($outboundManagement->channel == 'SMS') {
            $this->diffusionBySMS($formAnswers, $outboundManagement, $startDiffusionDateTime);
        }else if ($outboundManagement->channel == 'Email') {
            $this->diffusionBySMS($formAnswers, $outboundManagement, $startDiffusionDateTime);
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
                    $diffusion = $field->value;
                }
                $messageContent = str_replace("[[$field->id]]", $field->value,$messageContent);
            }
            $clients[] = [
                'diffusion' => $diffusion,
                'message' => $messageContent
            ];
        });

        $startHour = $outboundManagement->settings->start_hour;
        $endHour = $outboundManagement->settings->end_hour;
        $days = $outboundManagement->settings->days;

        dispatch((new DiffusionBySMS($clients, $startHour, $endHour, $days))->delay(Carbon::createFromFormat('Y-m-d H:i', "$startDiffusionDateTime")))
        ->onQueue('diffusions');
    }

    public function diffusionByEmail($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        $clients = [];
        $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
            $fields = json_decode($answer->structure_answer);
            $body = $outboundManagement->settings->email->body;
            $subject = $outboundManagement->settings->email->subject;
            foreach ($fields as $field) {
                if ($field->id == $outboundManagement->settings->sms->diffusion_field) {
                    $diffusion = $field->value;
                }
                $messageContent = str_replace("[[$field->id]]", $field->value,$body);
                $messageContent = str_replace("[[$field->id]]", $field->value,$subject);
            }
            //sender_email
            //replay_email
            $clients[] = [
                'body' => $outboundManagement->settings,
                'subject' => $messageContent,
                'to' => $diffusion,
                'attatchment' => [],
                'cc' => $messageContent,
                'cco' => $messageContent,
            ];
        });

        dispatch((new DiffusionByEmail($clients))->delay(Carbon::createFromFormat('Y-m-d H:i', "$startDiffusionDateTime")))
        ->onQueue('diffusions');
    }

    public function sendDiffusionBySMS(array $clients, string $startHour, string $endHour, array $days)
    {
        $notificationsService = new NotificationsService;
        
        foreach ($clients as $key => $client) {
            $now = Carbon::now('America/Bogota');
            $isGreaterThanOrEqualTo = $now->greaterThanOrEqualTo(Carbon::createFromTimeString($startHour, 'America/Bogota'));
            $isLessThan = $now->lessThan(Carbon::createFromTimeString($endHour, 'America/Bogota'));

            if ($isGreaterThanOrEqualTo && $isLessThan) {
                $notificationsService->sendSMS($client['message'], [$client['diffusion']]);
                unset($clients[$key]);
            } else {
                break;
            }
        }

        $todaysNumber = Carbon::now('America/Bogota')->dayOfWeekIso;
        $nextExcecution = null;

        foreach ($days as $day) {
            if ($todaysNumber < $day) {
                $nextExcecutionDay = $day - $todaysNumber; 
                $nextExcecution = $now->addDays($nextExcecutionDay);
                break;
            }
        }

        if (is_null($nextExcecution) && count($clients)) {
            $nextExcecutionDay = 7 - $todaysNumber + $days[0];
            $nextExcecution = $now->addDays($nextExcecutionDay);
        }

        if (!is_null($nextExcecution) && count($clients)) {
            dispatch((new DiffusionBySMS($clients, $startHour, $endHour, $days))->delay(Carbon::createFromFormat('Y-m-d H:i', "$nextExcecution")))
            ->onQueue('diffusions');
        }
    }
}
