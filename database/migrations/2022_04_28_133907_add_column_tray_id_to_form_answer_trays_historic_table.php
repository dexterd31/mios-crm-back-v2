<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnTrayIdToFormAnswerTraysHistoricTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_answer_trays_historic', function (Blueprint $table) {
            $table->foreignId('trays_id')->constrained('trays');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_answer_trays_historic', function (Blueprint $table) {
            $table->dropForeign('trays_id');
        });
    }
}
