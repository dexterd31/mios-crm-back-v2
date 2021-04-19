<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsInTraysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trays', function (Blueprint $table) {
            //
            $table->json('fields_exit');
            $table->json('fields_table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trays', function (Blueprint $table) {
            //
            $table->dropColumn('fields_exit');
            $table->dropColumn('fields_table');
        });
    }
}
