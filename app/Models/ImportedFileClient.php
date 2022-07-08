<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportedFileClient extends Model
{
    protected $table = 'imported_file_client';

    protected $fillable = [
        'client_new_id',
        'imported_file_id'
    ];

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

    /**
     * Filtra por el id del archivo importado.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param int $importedFile
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeImportedFileFilter($query, $importedFile)
    {
        if ($importedFile) {
            return $query->where('imported_file_id');
        }
    }
}
