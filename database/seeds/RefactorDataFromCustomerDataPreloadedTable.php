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

        $formIds = CustomerDataPreload::distinct()->pluck('form_id')->toArray();

        foreach ($formIds as $formId) {
            dispatch((new CreateClients($formId)))->onQueue('create-clients');
            sleep(1);
        }
    }
}
