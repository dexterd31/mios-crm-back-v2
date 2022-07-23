<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundManagementAttachment extends Model
{
    protected $fillable = [
        'outbound_management_id',
        'name',
        'path',
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    public function outboundManagement()
    {
        return $this->belongsTo(OutboundManagement::class);
    }
}
