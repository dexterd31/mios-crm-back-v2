<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineUser extends Model
{
    protected $fillable = [
        'rrhh_id',
        'form_id',
        'is_paused',
        'ciu_status',
        'role_id'
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function getEstatusAttributte()
    {
        return $this->is_paused ? 'En pausa' : 'Sin pausa';
    }

    public function scopeFormFilter($query, $formId)
    {
        if ($formId) {
            return $query->where('form_id', $formId);
        }
    }

    public function scopeRoleFilter($query, $roleId)
    {
        if ($roleId) {
            return $query->where('role_id', $roleId);
        }
    }
}
