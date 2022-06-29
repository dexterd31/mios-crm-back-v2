<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelAdvisorClientNew extends Model
{
    protected $table = 'rel_advisor_client_new';
    protected $primaryKey = 'id';
    protected $fillable = [
        'client_new_id',
        'rrhh_id',
        'managed'
    ];

    public function clientNew(){
        return $this->belongsTo(ClientNew::class,'client_new_id');
    }

    public function scopeRrhhFilter($query, $rrhhId)
    {
        if ($rrhhId) {
            return $query->where('rrhh_id', $rrhhId);
        }
    }

    public function scopeClientNewFilter($query, $clientId)
    {
        if ($clientId) {
            return $query->where('client_new_id', $clientId);
        }
    }

    public function scopeManagedFilter($query, $managed)
    {
        if (!is_null($managed)) {
            return $query->where('managed', $managed);
        }
    }
}
