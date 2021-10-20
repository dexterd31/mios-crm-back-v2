<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaveHistoricInTrays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trays', function (Blueprint $table) {
            $table->tinyInteger('save_historic')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('trays', 'save_historic')) {
            Schema::table('trays', function (Blueprint $table) {
                $table->dropColumn('save_historic');
            });

        }
    }
}
