<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldData extends Model
{
    protected $fillable = [
        'client_new_id',
        'field_data'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    /**
     * Consulta el cliente asociado al que esta asociada la informacion de los campos.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\ClientNew
     */
    public function client()
    {
        return $this->belongsTo(ClientNew::class);
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    /**
     * Filtra por id del cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param int $client
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeClientFilter($query, $client)
    {
        if ($client) {
            return $query->where('client_new_id', $client);
        }
    }

    //? Accessors ------------------------------------------------------------------------------------------------------

    /**
     * Retorna el atributo field_data ya decodificado.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param string $value
     * @return void
     */
    public function getFieldDataAttribute($value)
    {
        return json_decode($value);
    }

    //? Mutatos --------------------------------------------------------------------------------------------------------

    /**
     * Convierte a string al objeto que se le pase al atributo field_data, antes de ser almacenado en base de datos.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param object $value
     * @return void
     */
    public function setFieldDataAttribute($value)
    {
        $this->attributes['field_data'] = json_encode($value);
    }
}
