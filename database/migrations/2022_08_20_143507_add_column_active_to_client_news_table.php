<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnActiveToClientNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_news', function (Blueprint $table) {
            $table->boolean('status')->default(1)->comment('Estado del cliente, 1 - activo, 0 - inactivo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_news', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
