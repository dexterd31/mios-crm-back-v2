<?php

namespace App\Providers;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $token = JWTAuth::getToken();
            $user = new GenericUser(JWTAuth::getPayload($token)->toArray());
            $user->id = $user->sub;
            return $user;
        });

        Gate::define('admin', function ($user) {
            return in_array('crm::admin', $user->roles);
        });

        // Validar permiso para las acciones en form_answer
        Gate::define('form_answer', function($user){
            if (in_array('crm::admin', $user->roles) || in_array('bpms::solicitante', $user->roles) || in_array('bpms::responsable_bpms', $user->roles) 
                || in_array('ciu::supervisor', $user->roles) || in_array('crm::supervisor_crm', $user->roles) || in_array('crm::asesor', $user->roles) 
                || in_array('crm::calidad', $user->roles) || in_array('crm::datamarshall', $user->roles) || in_array('crm::backoffice', $user->roles) || in_array('crm::usuario_externo', $user->roles)) {
                return true;
            } else {
                return false;
            }
        });

        // Validar permisos de campaigns
        Gate::define('campaigns', function($user){
            if (in_array('crm::admin', $user->roles) || in_array('crm::supervisor_crm', $user->roles) || in_array('ciu::supervisor', $user->roles)) {
                return true;
            } else {
                return false;
            }
        });

        // Validar permisos de grupos
        Gate::define('groups', function($user){
            if (in_array('crm::admin', $user->roles) || in_array('crm::supervisor_crm', $user->roles) || in_array('ciu::supervisor', $user->roles)) {
                return true;
            } else {
                return false;
            }
        });
    }
}
