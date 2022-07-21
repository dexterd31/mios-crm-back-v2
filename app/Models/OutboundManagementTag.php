<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundManagementTag extends Model
{
    protected $fillable = [
        'outbound_management_id',
        'tag_id'
    ];
}
