<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedFile extends Model
{
    protected $fillable = [
        'name'
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    /**
     * Consulta los clientes cargados por archivo.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function clients()
    {
        return $this->belongsToMany(ClientNew::class, 'imported_file_client');
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    /**
     * Filtra por nombre del archivo.
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
}
