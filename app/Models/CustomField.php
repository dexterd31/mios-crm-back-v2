<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $fillable = [
        'form_id',
        'fields'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    /**
     * Consulta el formulario con el cual estan asociados los campos.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\Form
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    /**
     * Filtra por id de formulario.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param int $form
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeFormFilter($query, $form)
    {
        if ($form) {
            return $query->where('form_id', $form);
        }
    }

    //? Accesors -------------------------------------------------------------------------------------------------------

    /**
     * Retorna el atributo fields ya decodificado.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return object
     */
    public function getFieldsAttribute($value)
    {
        return json_decode($value);
    }

    //? Mutators -------------------------------------------------------------------------------------------------------

    /**
     * Convierte a string al objeto que se le pase al atributo fields, antes de ser almacenado en base de datos.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param object $value
     * @return void
     */
    public function setFieldsAttribute($value)
    {
        $this->attributes['fields'] = json_encode($value);
    }
}
