<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDataPreloadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_data_preloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained();
            $table->json('customer_data')->comment('Datos del cliente.');
            $table->boolean('to_update')->comment('Indica que si el registro existe, este se debe actualizar.');
            $table->bigInteger('adviser', false, true)->comment('Contiene el rrhh del asesor al que se asigna, en caso de no asignar, dejar en cero, para tomarlo como false.');
            $table->json('unique_identificator')->comment('Identificador unico del cliente.');
            $table->json('form_answer')->comment('Datos del cliente con formato de respuesta para el formulario.');
            $table->boolean('managed')->default(false)->comment('Indica si el registro ha sido gestionado o no.');
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
        Schema::dropIfExists('customer_data_preloads');
    }
}
