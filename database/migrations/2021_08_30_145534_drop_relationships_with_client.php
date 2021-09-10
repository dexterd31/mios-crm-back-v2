<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropRelationshipsWithClient extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('key_values', 'client_id'))
        {
            Schema::table('key_values', function (Blueprint $table) {
                if($this->foreignKeysExists('key_values', "key_values_client_id_foreign"))
                {
                    $table->dropForeign(['client_id']);
                }
                $table->unsignedBigInteger('client_id')->default(0)->change();
            });
        }

        if(Schema::hasColumn('form_answers', 'client_id'))
        {
            Schema::table('form_answers', function (Blueprint $table) {
                if($this->foreignKeysExists('form_answers', "form_answers_client_id_foreign"))
                {
                    $table->dropForeign(['client_id']);
                }
                $table->unsignedBigInteger('client_id')->default(0)->change();
            });
        }

        if(Schema::hasColumn('directories', 'client_id'))
        {
            Schema::table('directories', function (Blueprint $table) {
                if($this->foreignKeysExists('directories', "directories_client_id_foreign"))
                {
                    $table->dropForeign(['client_id']);
                }
                $table->unsignedBigInteger('client_id')->default(0)->change();
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
        //
    }

    public function foreignKeysExists($table, $foreignKey)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        $foreignKeys = array_map(function($key) {
            return $key->getName();
        }, $conn->listTableForeignKeys($table));

        return in_array($foreignKey, $foreignKeys);
    }
}
