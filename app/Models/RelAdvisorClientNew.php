<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelAdvisorClientNew extends Model
{
    protected $table = 'rel_advisor_client_new';
    protected $primaryKey = 'id';
    protected $fillable = ['client_new_id','rrhh_id'];

    public function clientNew(){
        return $this->belongsTo(ClientNew::class,'client_new_id');
    }
}
