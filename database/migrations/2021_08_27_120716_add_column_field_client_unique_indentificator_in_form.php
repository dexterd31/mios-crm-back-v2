<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnFieldClientUniqueIndentificatorInForm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('directories', function ($table)
        {
            $table->unsignedBigInteger('client_new_id'); 
        });

        Schema::table('key_values', function ($table)
        {
            $table->unsignedBigInteger('client_new_id'); 
        });
      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('fields_client_unique_identificator');
        });
    }
}
