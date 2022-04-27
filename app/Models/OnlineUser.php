<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineUser extends Model
{
    protected $fillable = [
        'rrhh_id',
        'form_id',
        'is_paused',
        'ciu_status',
        'role_id'
    ];

    /**
     * Formulario relacionado
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return object
     */
    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Devuelve el nombre del estado en el que se encuentra el usuario.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return string
     */
    public function getEstatusAttributte() : string
    {
        return $this->is_paused ? 'En pausa' : 'Sin pausa';
    }

    /**
     * Filtro por formulario
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param mixed $query
     * @param mixed $formId
     * @return object|void
     */
    public function scopeFormFilter($query, $formId)
    {
        if ($formId) {
            return $query->where('form_id', $formId);
        }
    }

    /**
     * Filtro por rol.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param mixed $query
     * @param mixed $roleId
     * @return object|void
     */
    public function scopeRoleFilter($query, $roleId)
    {
        if ($roleId) {
            return $query->where('role_id', $roleId);
        }
    }
}
