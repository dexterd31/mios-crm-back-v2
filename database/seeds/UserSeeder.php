<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = array(
            [
                'id_rhh' => '1',
                'state' => 1

            ],
            [
                'id_rhh' => '2',
                'state' => 1
            ],
            [
                'id_rhh' => '3',
                'state' => 1
            ],
            [
                'id_rhh' => '4',
                'state' => 1
            ],
            [
                'id_rhh' => '5',
                'state' => 1
            ]
        );

        foreach ($users as $user)
        {
            $User = new User();
            $User->id_rhh = $user['id_rhh'];
            $User->state = $user['state'];
            $User->save();
        }

    }
}
