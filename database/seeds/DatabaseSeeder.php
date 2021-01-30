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
        $this->call('FormSubTypeSeeder');
         $this->call('FormtypeSeeder');
         $this->call('FormSeeder');
         $this->call('SectionSeeder');
    }
}
