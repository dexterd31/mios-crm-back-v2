<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundManagementClientNewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_management_client_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_management_id')->comment('Id de la gestión.');
            $table->foreignId('client_new_id')->comment('Id del cliente');
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
        Schema::dropIfExists('outbound_management_client_new');
    }
}
