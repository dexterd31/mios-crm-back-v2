<?php

use Illuminate\Database\Seeder;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Report::insert([
            [
                'form_id' => 1,
                'reports_power_bi_id' => 1,
                'rrhh_id' => 1,
                'title' => "Pruba 1",
                'state' => 1,
            ],
            [
                'form_id' => 1,
                'reports_power_bi_id' => 2,
                'rrhh_id' => 1,
                'title' => "Pruba 2",
                'state' => 1,
            ],
            [
                'form_id' => 2,
                'reports_power_bi_id' => 3,
                'rrhh_id' => 1,
                'title' => "Pruba 3",
                'state' => 1,
            ],
        ]);
    }
}
