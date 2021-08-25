<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUserIdInUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('uploads', 'user_id'))
        {
            Schema::table('uploads', function (Blueprint $table) {
                if($this->foreignKeysExists('uploads', "uploads_user_id_foreign"))
                {
                    $table->dropForeign(['user_id']);
                }
                $table->dropColumn('user_id');
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
        Schema::table('uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->foreignId('user_id')->constrained('users');
        });
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
