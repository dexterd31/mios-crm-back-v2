<?php

namespace App\Models;

use App\Models\Attachment;
use App\Models\Tray;
use Illuminate\Database\Eloquent\Model;
use App\Models\ClientNew;
use App\Models\Form;
use App\Models\FormAnswersTray;

class FormAnswer extends Model
{
    protected $table = 'form_answers';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id','rrhh_id', 'client_id','channel_id','structure_answer', "client_new_id", "form_answer_index_data", "tipification_time", "conversation_id", 'status'];

    public function form(){
       return $this->hasMany('App\Models\Form','id');
    }

    public function forms(){
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function client(){
        return $this->belongsTo('App\Models\Client', 'client_id');
    }
 
    public function ClientNew(){
        return $this->belongsTo(ClientNew::class, 'client_new_id');
    }

    public function channel(){
        return $this->belongsTo('App\Models\Channel', 'channel_id'); 
    }

    public function atachments(){
        return $this->hasMany(Attachment::class);
    }

    public function trays(){
        return $this->belongsToMany(Tray::class, 'form_answers_trays');
    }

    public function formAnswersTrays(){
        return $this->hasMany(FormAnswersTray::class);
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    public function scopeFormFilter($query, $form)
    {
        if ($form) {
            return $query->where('form_id', $form);
        }
    }

    public function scopeClientFilter($query, $client)
    {
        if ($client) {
            return $query->where('client_new_id', $client);
        }
    }

    /**
     * Filtra por id rrhh
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.online>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param int $rrhh
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeRrhhFilter($query, $rrhh)
    {
        if ($rrhh) {
            $query->where('rrhh_id', $rrhh);
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
            return $query->whereDate('form_answers.updated_at', '>=', $from)->whereDate('form_answers.updated_at', '<=', $to);
        }
    }
}
