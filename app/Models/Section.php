<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Form;

class Section extends Model
{
    protected $table = 'sections';
    protected $PrimaryKey = 'id';
    protected $fillable = ['form_id', 'name_section', 'type_section','fields', 'collapse','duplicate','state'];

    public function Form(){
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function parameter(){
        return $this->hasMany('App\Models\Parameter','form_id');
    }

    public static function getFields($formId,$keysToSave){

        // $keysToSave = ['firstName','first_lastname','phone','email','source_data_crm_account_id','placa'];
         $sql = Section::where('form_id', $formId);

         $sql->where(function($query) use($keysToSave) {
             foreach ($keysToSave as $key) {
                 $query->orWhereJsonContains('fields', ['key'=>$key]);
             }
         });
         $sections = $sql->get();

         $fields = collect();

         $keysToSaveCollect = collect($keysToSave);

         foreach ($sections as $section) {
             foreach (json_decode($section->fields) as $key => $field) {
                 if($keysToSaveCollect->contains($field->key)) $fields->push($field);
             }
         }

         return $fields->all();
    }

    public function scopeFormFilter($query, $form)
    {
        if ($form) {
            return $query->where('form_id', $form);
        }
    }
}
