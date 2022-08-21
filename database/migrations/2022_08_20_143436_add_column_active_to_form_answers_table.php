<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnActiveToFormAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_answers', function (Blueprint $table) {
            $table->boolean('status')->default(1)->comment('Estado de la respuesta, 1 - activo, 0 - inactivo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_answers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
