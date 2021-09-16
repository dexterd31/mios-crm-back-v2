<?php

use Illuminate\Database\Seeder;

class EstabilitationSeeder extends Seeder
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
