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
        'form_answer',
        'custom_field_data',
        'tags'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    //? Filters --------------------------------------------------------------------------------------------------------

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

    //? Accessor -------------------------------------------------------------------------------------------------------

    public function getUniqueIdentificatorAttribute($value)
    {
        return json_decode($value);
    }

    public function getCustomerDataAttribute($value)
    {
        return json_decode($value);
    }

    public function getFormAnswerAttribute($value)
    {
        return json_decode($value);
    }

    public function getCustomFieldDataAttribute($value)
    {
        return json_decode($value);
    }

    public function getTagsAttribute($value)
    {
        return explode(',', $value);
    }

    //? Mutators -------------------------------------------------------------------------------------------------------

    public function setUniqueIdentificatorAttribute($value)
    {
        $this->attributes['unique_identificator'] = json_encode($value);
    }

    public function setCustomerDataAttribute($value)
    {
        $this->attributes['customer_data'] = json_encode($value);
    }

    public function setFormAnswerAttribute($value)
    {
        $this->attributes['form_answer'] = json_encode($value);
    }

    public function setCustomFieldDataAttribute($value)
    {
        $this->attributes['custom_field_data'] = json_encode($value);
    }

    public function setTagsAttribute($value)
    {
        $this->attributes['tags'] = implode(',', $value);
    }
}
