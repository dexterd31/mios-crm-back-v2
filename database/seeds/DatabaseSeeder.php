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
        // $this->call('CampaingSeeder');
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
