<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_logs', function (Blueprint $table) {
            $table->id();
            $table->string('name_form')->nullable();
            $table->json('filters')->nullable();
            $table->json('sections')->nullable();
            $table->tinyInteger('state')->nullable();
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('campaign_id')->constrained('campaings');
            $table->foreignId('form_id')->constrained('forms');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_logs');
    }
}
