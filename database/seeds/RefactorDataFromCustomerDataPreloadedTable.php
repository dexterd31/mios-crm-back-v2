<?php

use App\Models\CustomerDataPreload;
use Illuminate\Database\Seeder;

class RefactorDataFromCustomerDataPreloadedTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CustomerDataPreload::chunk(100, function ($customerDataPreloadeds) {
            foreach ($customerDataPreloadeds as $customerDataPreloaded) {
                $formAnswers = $customerDataPreloaded->form_answer;
                $refactorData = [];

                foreach ($formAnswers as $formAnswer) {
                    $refactorData[] = (object) [
                        $formAnswer->id => $formAnswer->value
                    ];
                }
                
                $customerDataPreloaded->form_answer = $refactorData;
                $customerDataPreloaded->save();
            }
        });
    }
}
