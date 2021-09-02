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

    public function userCanExecuteAction($action, $element)
    {
        $user = auth()->user();
        return isset($user->permissions) && isset($user->permissions->crm) && 
            isset($user->permissions->crm->$element) && isset($user->permissions->crm->$element->$action);
    }
}
