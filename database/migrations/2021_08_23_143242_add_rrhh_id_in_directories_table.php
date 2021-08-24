<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Directory;
use App\Models\User;
use PhpParser\Node\Stmt\Foreach_;

class AddRrhhIdInDirectoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->unsignedBigInteger('rrhh_id');
        });
        $directories = Directory::all();
        foreach ($directories as $directorie)
        {
            $directorie->rrhh_id = User::find($directorie->user_id)->id_rhh;
            $directorie->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->dropColumn('rrhh_id');
        });
    }
}
