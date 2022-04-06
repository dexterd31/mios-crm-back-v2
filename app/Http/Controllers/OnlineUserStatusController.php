<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\OnlineUserRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OnlineUserStatusController extends Controller
{
    protected $onlineUserRepository;

    public function __construct(OnlineUserRepository $onlineUserRepository)
    {
        $this->onlineUserRepository = $onlineUserRepository;    
    }

    public function registerUserOnline(array $data)
    {
        $this->onlineUserRepository->create($data);
    }

    public function removeUserRegistrationOnline(int $rrhh_id)
    {
        $this->onlineUserRepository->delete($rrhh_id);
    }

    public function validateCIUUserStatus(Request $request)
    {
        $this->validate($request,[
            'rrhh_id' => 'required|integer',
            'form_id' => 'required|exsist:forms,id',
            'status' => 'required|boolean',
            'ciu_status' => 'required|string',
            'role_id' => 'required|integer'
        ]);

        try {
            if ($request->status) {
                $data = $request->except('status');
                $this->registerUserOnline($data);
            } else {
                $this->removeUserRegistrationOnline($request->rrhh_id);
            }
    
            return response()->json(['status_code' => 200, 'message' => 'OK'], 200);

        } catch (Exception $e) {
            Log::error(" ERROR OnlineUserStatusController@registerUserOnline:\n
                Mensaje:\n
                {$e->getMessage()}
            ");

            return response()->json(['status_code' => 500, 'message' => 'Error interno'], 500);
        }

    }

    public function onlineUserReportByForm(int $formId, int $roleId)
    {
        $onlineUsers = $this->onlineUserRepository->allByFormAndRole($formId, $roleId)->get();

        return response()->json([
            'status_code' => 200,
            'online_users' => $onlineUsers,
            'count' => $onlineUsers->count()
        ], 200);
    }

    public function changePauseUserStatus(Request $request)
    {
        $this->validate($request, [
            'rrhh_id' => 'required|exists:online_users,rrhh_id',
            'is_paused' => 'required|boolean'
        ]);

        $this->onlineUserRepository->updateByRRHHId($request->rrhh_id, $request->only('is_paused'));

        return response()->json([
            'status_code' => 200,
            'message' => 'OK'
        ], 200);
    }
}
