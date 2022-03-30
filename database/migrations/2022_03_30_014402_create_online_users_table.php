<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnlineUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('online_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rrhh_id', false, true)->comment('Id RRHH del usuario');
            $table->bigInteger('role_id', false, true)->comment('Rol del usuario');
            $table->foreignId('form_id')->constrained();
            $table->string('ciu_status', false, true)->comment('Estado CIU del usuario');
            $table->boolean('is_paused')->default(0)->comment('Estado de pausa del usuario: 1 - pausado, 0 - sin pausa');
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
        Schema::dropIfExists('online_users');
    }
}
