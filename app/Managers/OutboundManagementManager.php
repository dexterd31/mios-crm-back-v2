<?php

namespace App\Managers;

use App\Jobs\DiffusionBySMS;
use App\Models\Channel;
use App\Models\FormAnswer;
use App\Models\OutboundManagement;
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
        if ($data['outbound_management_id']) {
            $outboundManagement = OutboundManagement::find($data['outbound_management_id']);
            $outboundManagement->name = $data['name'];
            $outboundManagement->settings = $data['settings'];
            $outboundManagement->save();
            
            $outboundManagement->tags()->detach();
            $outboundManagement->tags()->attach($data['tags']);

        } else {
            $channel = Channel::nameFilter($data['channel'])->first();
            $outboundManagement = OutboundManagement::create([
                'form_id' => $data['form_id'],
                'name' => $data['name'],
                'channel_id' => $channel->id,
                'settings' => $data['settings'],
            ]);

            $outboundManagement->tags()->attach($data['tags']);
        }

        return $outboundManagement;
    }

    public function createDiffusion($outboundManagement)
    {
        $formAnswers = FormAnswer::formFilter($outboundManagement->form_id)
        ->join('client_tag', 'client_tag.client_new_id', 'form_answers.client_new_id')
        ->whereIn('client_tag.tag_id', $outboundManagement->tags)->get(['structure_answer']);

        $startDiffusionDateTime = "{$outboundManagement->settings->start_diffusion_date} {$outboundManagement->settings->start_diffusion_time}";

        if ($outboundManagement->channel_id == Channel::nameFilter('SMS')->firts()->id) {
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

        dispatch((new DiffusionBySMS($clients))->delay(Carbon::createFromFormat('Y-m-d H:i', "$startDiffusionDateTime")))
        ->onQueue('diffusions');
    }

    public function diffusionByEmail($formAnswers, $outboundManagement, $startDiffusionDateTime)
    {
        $clients = [];
        $formAnswers->each(function ($answer) use ($outboundManagement, &$clients) {
            $fields = json_decode($answer->structure_answer);
            $messageContent = $outboundManagement->settings->sms->message_content;
            foreach ($fields as $field) {
                if ($field->id == $outboundManagement->settings->sms->diffusion_field) {
                    $diffusion = $field->value;
                }
                $messageContent = str_replace("[[$field->id]]", $field->value,$messageContent);
            }
            $clients[] = [
                'body' => $outboundManagement->settings,
                'subject' => $messageContent,
                'to' => $messageContent,
                'attatchment' => $messageContent,
                'cc' => $messageContent,
                'cco' => $messageContent,
            ];
        });
    }
}
