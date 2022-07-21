<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormWhatsappAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_whatsapp_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->comment('Id del formulario.');
            $table->foreignId('whatsapp_account_id')->comment('Id de la cuenta de whatsapp.');
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
        Schema::dropIfExists('form_whatsapp_account');
    }
}
