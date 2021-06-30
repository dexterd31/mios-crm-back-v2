<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('UserSeeder');
        $this->call('GroupsSeeder');
<<<<<<< HEAD
        // $this->call('CampaingSeeder');
=======
        //$this->call('CampaingSeeder');
>>>>>>> 40c6a0bb47eabd5a48ad43f5a8f4b457d6cc68a3
        $this->call('ChannelSeeder');
        $this->call('FormtypeSeeder');
        $this->call('FormSeeder');
        $this->call('SectionSeeder');
        $this->call('DocumentTypeSeeder');
        $this->call('ClientSeeder');
        $this->call('GroupUserSeeder');
        $this->call('FormAnswerSeeder');
        $this->call('RolCrmSeeder');
        $this->call('ModuleCrmSeeder');
        $this->call('PermissionCrmSeeder');
        $this->call('ApiConnections');
        $this->call('SetMenuCiuIdInModulesCrmSeeder');
    }
}
