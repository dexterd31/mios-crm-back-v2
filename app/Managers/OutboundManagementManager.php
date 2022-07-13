<?php

namespace App\Managers;

use App\Models\OutboundManagement;

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
        });

        return $outboundManagement;
    }
}
