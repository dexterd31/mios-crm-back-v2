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
                'username' => 'olme',
                'state' => 1

            ],
            [
                'id_rhh' => '2',
                'username' => 'javier',
                'state' => 1
            ],
            [
                'id_rhh' => '3',
                'username' => 'karol',
                'state' => 1
            ],
            [
                'id_rhh' => '4',
                'username' => 'nicol',
                'state' => 1
            ],
            [
                'id_rhh' => '5',
                'username' => 'juan',
                'state' => 1
            ]
        );

        foreach ($users as $user)
        {
            $User = new User();
            $User->id_rhh = $user['id_rhh'];
            $User->username = $user['username'];
            $User->state = $user['state'];
            $User->save();
        }

    }
}
