<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use ApiResponse;

    protected function authUser()
    {
        return auth()->user();
    }

    public function saveModel($model)
    {
        $model->save();
        return $model;
    }
}
