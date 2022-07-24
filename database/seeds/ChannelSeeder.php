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
                'name_channel' => 'Widget'
            ],
            [
                'name_channel' => 'Whatsapp'
            ],
            [
                'name_channel' => 'Messenger'
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
