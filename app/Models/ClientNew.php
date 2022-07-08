<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ClientNew extends Model
{
    protected $table = 'client_news';
    protected $PrimaryKey = 'id';
    protected $fillable = [
        "form_id",
        "information_data",
        "unique_indentificator",
    ];

    //? Relations ------------------------------------------------------------------------------------------------------

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function directory()
    {
        return $this->hasOne(Directory::class);
    }

    public function keyValues()
    {
        return $this->hasMany(KeyValue::class);
    }

    public function formanswer()
    {
        return $this->hasMany(FormAnswer::class, 'client_id');
    }

    /**
     * Consulta las etiquetas asociados al cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'client_tag');
    }

    /**
     * Consulta los datos de los campos personalizados asociados al cliente.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return App\Models\CustomFieldData
     */
    public function customFieldData()
    {
        return $this->hasOne(CustomFieldData::class);
    }

    /**
     * Consulta el nombre del archivo del cual se importo.
     * @author Edwin David Sanchez Balbin <e.sanchez@montechelo.com.co>
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function importedFiles()
    {
        return $this->belongsToMany(ImportedFile::class, 'imported_file_client');
    }

    //? Filters --------------------------------------------------------------------------------------------------------

    /**
     * Undocumented function
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param int $formId
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeFormFilter($query, $formId)
    {
        if ($formId) {
            return $query->where('form_id', $formId);
        }
    }

    /**
     * Filtra la fecha de actualiazción entre un lapso de fechas dadas.
     *
     * @param Illuminate\Database\Query\Builder $query
     * @param string $from Fecha inicial
     * @param string $to Fecha final
     * @return Illuminate\Database\Query\Builder
     */
    public function scopeUpdatedAtBetweenFilter($query, $from, $to)
    {
        if ($from && $to) {
            return $query->whereDate('updated_at', '>=', $from)->whereDate('updated_at', '<=', $to);
        }
    }
}
