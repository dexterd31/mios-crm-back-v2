<?php

use Illuminate\Database\Seeder;
use App\Models\Campaing;

class CampaingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $campaigns = array(
            [
                'group_id' => '1',
                'name_campaign' => 'Claro ventas'
            ],
            [
                'group_id' => '1',
                'name_campaign' => 'Claro compras'
            ]
        );

        foreach ($campaigns as $campaign)
        {
            $Campaign = new Campaing();
            $Campaign->group_id = $campaign['group_id'];
            $Campaign->name_campaign = $campaign['name_campaign'];
            $Campaign->save();
        }
    }
}
