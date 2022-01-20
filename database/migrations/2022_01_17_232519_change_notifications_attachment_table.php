<?php

use App\Models\NotificationsAttatchment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeNotificationsAttachmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $notificationsAttachments = NotificationsAttatchment::all()->toArray();

        Schema::table('notifications_attatchment', function (Blueprint $table) {
            $table->renameColumn('static_atachment', 'type_attachment');
            $table->renameColumn('dinamic_atachment', 'file_attachment');
        });

        foreach ($notificationsAttachments as $notificationsAttachment)
        {
            $saved = DB::table('notifications_attatchment')->where('id', $notificationsAttachment['id'])->update([
                'notifications_id' => $notificationsAttachment['notifications_id'],
                'type_attachment' => !is_null($notificationsAttachment['static_atachment']) ? 'static' : 'dynamic',
                'file_attachment' => $notificationsAttachment['static_atachment'] ?? $notificationsAttachment['dinamic_atachment'],
                'route_atachment' => $notificationsAttachment['route_atachment'],
                'created_at' => $notificationsAttachment['created_at'],
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notifications_attatchment', function (Blueprint $table) {
            $table->renameColumn('type_attachment', 'static_atachment');
            $table->renameColumn('file_attachment', 'dinamic_atachment');
        });
    }
}