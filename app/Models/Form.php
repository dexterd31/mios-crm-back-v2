<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    protected $table = 'forms';
    protected $PrimaryKey = 'id';
    protected $fillable = ['group_id', 'form_type_id', 'name_form','filters','state', 'seeRoles', 'fields_client_unique_identificator'];

    //? Relations ------------------------------------------------------------------------------------------------------
    
    public function formtype()
    {
        return $this->belongsTo(FormType::class, 'form_type_id');
    }

    public function trays()
    {
        return $this->hasMany(Tray::class);
    }

    public function section()
    {
        return $this->hasMany(Section::class);
    }

    public function group()
    {
        return $this->hasOne(Group::class,'group_id');
    }

    public function stateform()
    {
        return $this->hasMany(StateForm::class,'form_id');
    }

    public function formAnswers()
    {
        return $this->hasMany(FormAnswer::class);
    }

    public function keyvalue()
    {
        return $this->hasMany(KeyValue::class,'form_id');
    }
    
    public function upload()
    {
        return $this->hasMany(Upload::class,'form_id');
    }

    public function directory()
    {
        return $this->hasMany(Directory::class);
    }

    public function apiConnection()
    {
        return $this->hasMany(ApiConnection::class,'form_id');
    }

    public function apiQuestion()
    {
        return $this->hasMany(ApiQuestion::class,'api_id');
    }

    public function template()
    {
        return $this->hasMany(Template::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'report_id');
    }

    /**
     * Consulta las etiquetas asociados al formulario.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * Consulta los campos personalizados asociados al formulario.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\CustomField
     */
    public function cutomFields()
    {
        return $this->hasOne(CustomField::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'form_product');
    }

    public function whatsappAccounts()
    {
        return $this->belongsToMany(WhatsappAccount::class, 'form_whatsapp_account');
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    public function scopeGroupInFilter($query, array $ids)
    {
        return $query->whereIn('group_id', $ids);
    }
}
