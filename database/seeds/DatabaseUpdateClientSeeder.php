<?php

use Illuminate\Database\Seeder;

class DatabaseUpdateClientSeeder extends Seeder
{
  /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('ClientNewSeeder');
        $this->call('FieldsClientUniqueIdentificatorSeeder');
        $this->call('UpdateFormAnswerSeeder');
    }
}
