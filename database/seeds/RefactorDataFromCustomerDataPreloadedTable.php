<?php

use App\Jobs\CreateClients;
use App\Models\CustomerDataPreload;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class RefactorDataFromCustomerDataPreloadedTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // CustomerDataPreload::chunk(100, function ($customerDataPreloadeds) {
        //     foreach ($customerDataPreloadeds as $customerDataPreloaded) {
        //         $formAnswers = $customerDataPreloaded->form_answer;
        //         $refactorData = [];

        //         foreach ($formAnswers as $formAnswer) {
        //             $refactorData[] = (object) [
        //                 $formAnswer->id => $formAnswer->value
        //             ];
        //         }
                
        //         $customerDataPreloaded->form_answer = $refactorData;
        //         $customerDataPreloaded->save();
        //     }
        // });

        foreach ([45,91,92,93,95,96,97,98,99,105,109,106,110,111,112,133,114,115] as $formId) {
            dispatch((new CreateClients($formId))->delay(Carbon::now()->addSeconds(1)))->onQueue('create-clients');
            sleep(1);
        }
    }
}
