<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tray;
use App\Models\FormAnswersTray;

class RelTrayUser extends Model
{
    protected $table = 'rel_trays_users';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        "trays_id",
        "rrhh_id"
    ];

}
