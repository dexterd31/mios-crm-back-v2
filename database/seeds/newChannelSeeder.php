<?php

use App\Models\Channel;
use Illuminate\Database\Seeder;

class newChannelSeeder extends Seeder
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
                'name_channel' => 'Email'
            ],
            [
                'name_channel' => 'VideoChat'
            ],
            [
                'name_channel' => 'Voice'
            ],
            [
                'name_channel' => 'SMS'
            ],
            [
                'name_channel' => 'Redes Sociales'
            ],
            [
                'name_channel' => 'Chat'
            ],
            [
                'name_channel' => 'Videollamada'
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
