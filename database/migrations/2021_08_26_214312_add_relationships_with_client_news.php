<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;


class AddRelationshipsWithClientNews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('directories', 'client_new_id'))
        {
            Schema::table('directories', function ($table)
            {
                $table->unsignedBigInteger('client_new_id')->default(0); 
            });
        }

        if(!Schema::hasColumn('key_values', 'client_new_id'))
        {
            Schema::table('key_values', function ($table)
            {
                $table->unsignedBigInteger('client_new_id')->default(0); 
            });
        }

        if(!Schema::hasColumn('form_answers', 'client_new_id'))
        {
            Schema::table('form_answers', function ($table)
            {
                $table->unsignedBigInteger('client_new_id')->default(0); 
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('directories', 'client_new_id'))
        {
            Schema::table('directories', function ($table)
            {
                $table->dropColumn('client_new_id'); 
            });
        }

        if(Schema::hasColumn('key_values', 'client_new_id'))
        {
            Schema::table('key_values', function ($table)
            {
                $table->dropColumn('client_new_id'); 
            });
        }

        if(Schema::hasColumn('form_answers', 'client_new_id'))
        {
            Schema::table('form_answers', function ($table)
            {
                $table->dropColumn('client_new_id');
            });
        }
    }
}
