<?php

use Illuminate\Database\Seeder;
use App\Models\Group;

class GroupsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $groups = array(
            [
                'campaign_id' => 1,
                'name_group' => 'grupo de claro ventas',
                'description' => 'prueba'
            ],

            [
                'campaign_id' => 1,
                'name_group' => 'grupo de claro compras',
                'description' => 'prueba 2'
            ]
        );

        foreach ($groups as $group)
        {
            $Group = new Group();
            $Group->campaign_id = $group['campaign_id'];
            $Group->name_group = $group['name_group'];
            $Group->description = $group['description'];
            $Group->save();
        }
    }
}
