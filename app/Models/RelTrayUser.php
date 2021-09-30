<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tray;

class RelTrayUser extends Model
{
    protected $table = 'rel_trays_users';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        "nombre",
        "id", 
        "trays_id", 
        "rrhh_id"
    ];

    public function Form()
    {
        return $this->belongsTo(Tray::class, 'trays_id');
    }
}
