<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'sections';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id', 'name_section', 'type_section','fields', 'collapse'];

    public function Form(){
        return $this->belongsTo('App\Models\Form', 'id');
    }

    public function parameter(){
        return $this->hasMany('App\Models\Parameter','form_id');
    }

    public static function getFields($formId, $keys){

        $fields = json_decode(Section::where('form_id', $formId)
        ->whereJsonContains('fields', ['key' =>'placa'])
        ->get()->fields);
        $key = 'firstName';


        // $field = collect($fields)->filter(function($x) use ($key){
        //     return $x->key == $key;
        // })->first();
    }

}
