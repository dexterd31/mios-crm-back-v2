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

   /**
    * @desc Verifica si el usuario tiene permisso de ejecutar una acción en el modulo
    * @param string -> $action - la Key del actionPermission en base de datos (acion que se verifica)
    * @param string -> $element - el name del modules_crm en base de datos (modulo que se verifica)
    * @return bool -> retorna informacion si el usuario tiene permisso de ejecutar una acción en el modulo
    * @author João Alfonso Beleño
    */ 
    public function userCanExecuteAction($action, $module)
    {
        $user = auth()->user();
        return isset($user->permissions) && isset($user->permissions->crm) && 
            isset($user->permissions->crm->$module) && isset($user->permissions->crm->$module->$action);
    }
}
