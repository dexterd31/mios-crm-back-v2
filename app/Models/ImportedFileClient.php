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

    public function scopeClientFilter($query, $client)
    {
        if ($client) {
            return $query->where('client_new_id', $client);
        }
    }

    public function scopeImportedFileFilter($query, $importedFile)
    {
        if ($importedFile) {
            return $query->where('imported_file_id');
        }
    }
}
