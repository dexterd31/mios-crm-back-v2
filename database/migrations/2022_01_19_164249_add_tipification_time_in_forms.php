<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipificationTimeInForms extends Migration
{
    private const DEFAULT_TIPIFICATION_TIME = 0;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->boolean('tipification_time')
                ->nullable(false)
                ->default(self::DEFAULT_TIPIFICATION_TIME)
                ->comment('si el cronometro se debe mostrar en el formulario el valor debe ser 1 si no mostramos el cronometro su valor será 0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('forms','tipification_time')){
            Schema::table('forms', function (Blueprint $table) {
                $table->dropColumn('tipification_time');
            });
        }
    }
}
