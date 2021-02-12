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
                'user_id' => '1',
                'name_group' => 'grupo de claro ventas'
            ],
            [
                'user_id' => '1',
                'name_group' => 'grupo de claro compras'
            ]
        );

        foreach ($groups as $group)
        {
            $Group = new Group();
            $Group->user_id = $group['user_id'];
            $Group->name_group = $group['name_group'];
            $Group->save();
        }
    }
}
