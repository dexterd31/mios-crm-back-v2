<?php

use Illuminate\Database\Seeder;

class StabilizationSeeder extends Seeder
{
    public static $ID_FORM = 10;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('EstabilisationSections');
        $this->call('EstabilisationFormAnswerSeeder');
        $this->call('EstabilisationClientNewSeeder');
        $this->call('EstabilisationKeyValueSeeder');
    }
}
