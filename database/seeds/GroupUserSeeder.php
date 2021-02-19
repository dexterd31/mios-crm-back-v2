<?php

use Illuminate\Database\Seeder;
use App\Models\GroupUser;

class GroupUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $groupusers = array(
            [
                'group_id' => 1,
                'user_id' => 1,
               
            ],
            [
                'group_id' => 2,
                'user_id' => 2,
            ]
    );

    foreach($groupusers as $groupuser)
    {
        $groupusers = new GroupUser();
        $groupusers->group_id = $groupuser['group_id'];
        $groupusers->user_id = $groupuser['user_id'];
        $groupusers->save();
    }
    }
}
