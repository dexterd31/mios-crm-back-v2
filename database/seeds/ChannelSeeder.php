<?php

use Illuminate\Database\Seeder;
use App\Models\Channel;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $channels = array(
            [
                'name_channel' => 'Llamada'
            ],
            [
                'name_channel' => 'Chat'
            ],
            [
                'name_channel' => 'Videollamada'
            ],
            [
                'name_channel' => 'Redes Sociales'
            ]
        );

        foreach ($channels as $channel)
        {
            $Channel = new Channel();
            $Channel->name_channel = $channel['name_channel'];
            $Channel->save();
        }
    }
}
