<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $table = 'document_types';
    protected $PrimaryKey = 'id';
    protected $fillable = ['name_type_document'];

    public function client(){
        return $this->hasOne('App\Models\Client');
    }
}
