<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipsWithClientNews extends Migration
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
            $table->foreignId('client_new_id')->constrained('client_news'); 
        });

        Schema::table('key_values', function ($table)
        {
            $table->foreignId('client_new_id')->constrained('client_news'); 
        });
      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
