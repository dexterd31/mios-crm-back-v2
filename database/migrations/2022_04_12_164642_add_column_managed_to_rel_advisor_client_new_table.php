<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnManagedToRelAdvisorClientNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rel_advisor_client_new', function (Blueprint $table) {
            $table->boolean('managed')->default(0)->comment('Flag que indica si el cliente ya fue gestionado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rel_advisor_client_new', function (Blueprint $table) {
            $table->dropColumn('managed');
        });
    }
}
