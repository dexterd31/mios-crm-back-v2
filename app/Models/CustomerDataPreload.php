<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDataPreload extends Model
{
    protected $fillable = [
        'form_id',
        'customer_data',
        'to_update',
        'adviser',
        'unique_identificator',
        'form_answer'
    ];

    //? Relaciones -----------------------------------------------------------------------------------------------------

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    //? Filtros --------------------------------------------------------------------------------------------------------

    public function scopeFormFilter($query, $formId)
    {
        if ($formId) {
            return $query->where('form_id', $formId);
        }
    }

    public function scopeAdviserFilter($query, $adviserId)
    {
        if ($adviserId) {
            return $query->where('adviser', $adviserId);
        }
    }

    public function scopeToUpdateFilter($query, $toUpdate)
    {
        if ($toUpdate) {
            return $query->where('to_update', $toUpdate);
        }
    }

    public function scopeManagedFilter($query, $managed)
    {
        if ($managed) {
            return $query->where('managed', $managed);
        }
    }
}
