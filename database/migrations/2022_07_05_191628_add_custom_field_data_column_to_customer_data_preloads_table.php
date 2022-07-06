<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomFieldDataColumnToCustomerDataPreloadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_data_preloads', function (Blueprint $table) {
            $table->json('custom_field_data')->nullable()->comment('Datos de los campos personalizados');
            $table->json('tags')->nullable()->comment('Etiquetas asignadas.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_data_preloads', function (Blueprint $table) {
            $table->dropColumn('custom_field_data');
            $table->dropColumn('tags');
        });
    }
}
