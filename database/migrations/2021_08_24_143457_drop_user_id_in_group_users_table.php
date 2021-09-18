<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUserIdInGroupUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('group_users', 'user_id'))
        {
            Schema::table('group_users', function (Blueprint $table) {
                if($this->foreignKeysExists('group_users', "group_users_user_id_foreign"))
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
        Schema::table('group_users', function (Blueprint $table) {
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
