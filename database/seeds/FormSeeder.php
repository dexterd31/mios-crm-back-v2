<?php

use Illuminate\Database\Seeder;
use App\Models\Form;
class FormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Form = array(
            [
                'group_id' => 1,
                'campaign_id' => 1,
                'form_type_id' => 1,
                'name_form' => 'Laika',
                'key' => '1'
            ],
            [
                
                'group_id' => 1,
                'campaign_id' => 1,
                'form_type_id' => 1,
                'name_form' => 'SOAT',
                'key' => '2'
            ]
        );

        foreach ($Form as $form)
        {
            $Form = new Form();
            $Form->group_id = $form['group_id'];
            $Form->campaign_id = $form['campaign_id'];
            $Form->form_type_id = $form['form_type_id'];
            $Form->name_form = $form['name_form'];
            $Form->key = $form['key'];
            $Form->save();
        }
    }
}
