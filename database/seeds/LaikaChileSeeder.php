<?php

use App\Models\FormAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaikaChileSeeder extends Seeder
{
    const LAIKA_CHILE_FORM_ID = 1;
    protected $formAnswerModel;
    protected $structureCollection;

    public function __construct(FormAnswer $formAnswerModel)
    {
        $this->formAnswerModel = $formAnswerModel;
        $this->structureCollection = collect(json_decode(Storage::get('estructura_laika.json')));
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $answers = $this->formAnswerModel->where('form_id',self::LAIKA_CHILE_FORM_ID)->get(['id','structure_answer']);
        if(count($answers) > 0){
            $answers->each(function ($answer){
                $newAnswer = $this->replaceAnswerInStructure($answer->structure_answer,$answer->id);
                $answer->structure_answer = json_encode($newAnswer);
                $answerSave = $answer->save();
            });
        }
    }

    /**
     * @desc: reemplaza los valores de la estructura de la respuesta por los valores solicitados en la collecion structureCollection
     * @param $answer
     * @param $id
     * @return array
     */
    private function replaceAnswerInStructure($answer,$id){
        $newAnswer = array();
        foreach(json_decode($answer) as $answerItem){
            $this->structureCollection->each(function ($item) use (&$answerItem, &$newAnswer, $id){
                if($answerItem->id === $item->id){
                    Log::info("SE MODIFICA ID $id");
                    $answerItem->id = $item->replace->id;
                    $answerItem->key = $item->replace->key;
                    foreach($item->replace->values as $value){
                        if($answerItem->value == $value->old){
                            $answerItem->value = $value->new;
                        }
                    }
                }
            });
            array_push($newAnswer,$answerItem);
        }
        return $newAnswer;
    }
}
