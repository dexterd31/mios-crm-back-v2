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
                
                'name_group' => 'grupo de claro ventas',
                'description' => 'prueba'
            ],
            [
                
                'name_group' => 'grupo de claro compras',
                'description' => 'prueba 2'
            ]
        );

        foreach ($groups as $group)
        {
            $Group = new Group();
            $Group->name_group = $group['name_group'];
            $Group->description = $group['description'];
            $Group->save();
        }
    }
}
