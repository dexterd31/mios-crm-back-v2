<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiQuestion extends Model
{
    protected $table = 'api_questions';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        'form_id',
        'api_id',
        'relationship'
    ];

    public function form()
    {
        return $this->belongsTo('App\Models\Form', 'form_id');
    }

    public function apiConnetion()
    {
        return $this->belongsTo('App\Models\ApiConnection', 'api_id');
    }
}
