<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDataPreload extends Model
{
    protected $fillable = [
        'form_id',
        'customer_data',
        'to_update',
        'adviser',
        'unique_identificator',
        'form_answer',
        'custom_field_data',
        'tags',
        'imported_file_id'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    /**
     * Consulta el formulario asociado.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\From
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Consulta el nombre del archivo del cual fue importado el registro.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\ImportedFile
     */
    public function importedFile()
    {
        return $this->belongsTo(ImportedFile::class);
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    /**
     * Filtra por id del formulario.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     * 
     * @param Illuminate\Database\Query\Builder $query $query
     * @param int $formId
     * @return Illuminate\Database\Query\Builder $query
     */
    public function scopeFormFilter($query, $formId)
    {
        if ($formId) {
            return $query->where('form_id', $formId);
        }
    }

    /**
     * Filtra por el rrhh_id del asesor.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param int $adviserId
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeAdviserFilter($query, $adviserId)
    {
        if ($adviserId) {
            return $query->where('adviser', $adviserId);
        }
    }

    /**
     * Filtra los datos que se quieran o no actualizar.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param boolean $toUpdate
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeToUpdateFilter($query, $toUpdate)
    {
        if ($toUpdate) {
            return $query->where('to_update', $toUpdate);
        }
    }

    /**
     * Fitra por getionado o no.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param boolean $managed
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeManagedFilter($query, $managed)
    {
        if ($managed) {
            return $query->where('managed', $managed);
        }
    }

    //? Accessor -------------------------------------------------------------------------------------------------------

    /**
     * Retorna el identificador unico decodificado.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return object
     */
    public function getUniqueIdentificatorAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Retorna los datos del cliente decodificados.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return object
     */
    public function getCustomerDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Retorna la respuesta del cliente decodificada.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return object
     */
    public function getFormAnswerAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Retorna los datos de los campos personalizados decodificados.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return object
     */
    public function getCustomFieldDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Retorna los tags asociados al cliente decodificados.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return array
     */
    public function getTagsAttribute($value)
    {
        return json_decode($value);
    }

    //? Mutators -------------------------------------------------------------------------------------------------------

    /**
     * Codifica el identificador unico.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param object|array $value
     * @return void
     */
    public function setUniqueIdentificatorAttribute($value)
    {
        $this->attributes['unique_identificator'] = json_encode($value);
    }

    /**
     * Codifica los datos del cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param object|array $value
     * @return void
     */
    public function setCustomerDataAttribute($value)
    {
        $this->attributes['customer_data'] = json_encode($value);
    }

    /**
     * Codifica la respuesta del cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param object|array $value
     * @return void
     */
    public function setFormAnswerAttribute($value)
    {
        $this->attributes['form_answer'] = json_encode($value);
    }

    /**
     * Codifica los datos de los campos personalizados.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param object|array $value
     * @return void
     */
    public function setCustomFieldDataAttribute($value)
    {
        $this->attributes['custom_field_data'] = json_encode($value);
    }

    /**
     * Codifica los id de los tags.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param array $value
     * @return void
     */
    public function setTagsAttribute($value)
    {
        $this->attributes['tags'] = json_encode($value);
    }
}
