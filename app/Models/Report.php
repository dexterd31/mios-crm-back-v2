<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Form;

class Report extends Model
{
    protected $table = 'reports';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'form_id',
        'reports_power_bi_id',
        'rrhh_id',
        'title',
        'state'
    ];

    public function module()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
}
