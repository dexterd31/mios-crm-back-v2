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

        foreach ([45,91,92,97,110,111,122,125,129,148,155,157,162,164,166,167,171,173,176,177,178,189,205,207,208,219,236,243,245,252,253,256,271,272,278,279,280,282,284,285,286,289] as $formId) {
            dispatch((new CreateClients($formId))->delay(Carbon::now()->addSeconds(1)))->onQueue('create-clients');
            sleep(1);
        }
    }
}
