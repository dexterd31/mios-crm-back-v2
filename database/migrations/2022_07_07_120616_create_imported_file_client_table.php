<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportedFileClientTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_file_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_file_id')->comment('Id del archivo importado');
            $table->foreignId('client_new_id')->comment('Id del cliente.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imported_file_client');
    }
}
