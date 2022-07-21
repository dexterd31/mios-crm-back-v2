<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundManagementAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_management_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_management_id')->comment('Id de la gestion de salida');
            $table->string('name')->comment('Nombre del archivo.');
            $table->string('path')->comment('Ubicación del archivo.');
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
        Schema::dropIfExists('outbound_management_attachments');
    }
}
