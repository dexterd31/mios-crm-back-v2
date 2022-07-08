<?php

namespace App\Models;

use App\Models\ClientNew;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'form_id'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    /**
     * Consulta el formulario asociado a la etiqueta.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\Form
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Consulta los clientes que estan asociados a la etiqueta.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function clients()
    {
        return $this->belongsToMany(ClientNew::class, 'client_tag');
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    /**
     * Filtra las etiquetas por id de formuario.
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

    /**
     * Filtra los tag por nombre.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param string $name
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeNameFilter($query, $name)
    {
        if ($name) {
            return $query->where('name', 'LIKE', "%$name%");
        }
    }

    //? Muttators ------------------------------------------------------------------------------------------------------

    /**
     * Pasa a minuscula el valor del nombre antes de ser almacenado en base de datos.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return void
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }
}
