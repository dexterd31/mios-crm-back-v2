<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundManagement extends Model
{
    protected $fillable = [
        'form_id',
        'name',
        'channel',
        'settings',
        'total',
        'status',
    ];

    //? Retaltions -----------------------------------------------------------------------------------------------------

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function tags()
    {
        $this->belongsToMany(Tag::class, 'outbound_management_tags');
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    public function scopeFormFilter($query, $form)
    {
        if ($form) {
            return $query->where('form_id', $form);
        }
    }

    /**
     * Filtra la fecha de actualiazción entre un lapso de fechas dadas.
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param string $from Fecha inicial
     * @param string $to Fecha final
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeUpdatedAtBetweenFilter($query, $from, $to)
    {
        if ($from && $to) {
            return $query->whereDate('updated_at', '>=', $from)->whereDate('updated_at', '<=', $to);
        }
    }

    //? Accessors ------------------------------------------------------------------------------------------------------

    public function getSettingsAttribute($value)
    {
        return json_decode($value);
    }

    //? Mutators -------------------------------------------------------------------------------------------------------

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = json_encode($value);
    }
}
