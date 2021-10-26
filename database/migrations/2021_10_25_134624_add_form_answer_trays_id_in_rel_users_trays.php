<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormAnswerTraysIdInRelUsersTrays extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rel_trays_users', function (Blueprint $table) {
            $table->bigInteger('form_answers_trays_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('rel_trays_users', 'form_answers_trays_id')) {
            Schema::table('rel_trays_users', function (Blueprint $table) {
                $table->dropColumn('form_answers_trays_id');
            });

        }
    }
}
