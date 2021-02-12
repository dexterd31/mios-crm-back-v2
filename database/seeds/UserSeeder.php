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
                'username' => 'nicolr',
                'status' => 'online',
                'state' => '1'

            ],
            [
                'id_rhh' => '1',
                'username' => 'juanr',
                'status' => 'online',
                'state' => '1'
            ]
        );

        foreach ($users as $user)
        {
            $User = new User();
            $User->id_rhh = $user['id_rhh'];
            $User->username = $user['username'];
            $User->status = $user['status'];
            $User->state = $user['state'];
            $User->save();
        }

    }
}
