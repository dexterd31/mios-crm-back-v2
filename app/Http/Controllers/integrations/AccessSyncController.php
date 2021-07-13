<?php

namespace App\Http\Controllers\integrations;

use App\Http\Controllers\Controller;
use App\Models\TokenReader;
use App\Services\SyncServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

use function GuzzleHttp\json_decode;

class AccessSyncController extends Controller
{
    //
    private $user;
    private $syncService;

    public function __construct(SyncServices $syncService){
        $this->syncService = $syncService;
        $this->enviroment = Config::get('constant.enviroments');

        $this->middleware('auth', ['except' => ['login']]);


    }

    public function login(Request $request){
        if(!$request->has('origin')) return $this->errorResponse('origin es un valor requerido',404);

        if( ! $request->has('username') ) return $this->errorResponse('username es un valor requerido',404);
        if( ! $request->has('password') ) return $this->errorResponse('password es un valor requerido',404);

        $origin = $this->enviroment[$request->origin];
        if( !isset( $origin  ) ) return $this->errorResponse('El Origen (origin) de los datos es invalido',404);

        $params = array(
            'username'=>$request->username,
            'password'=>$request->password,
            'fingerprint'=>uniqid()
        );
       $userToken = $this->syncService->login( $origin['ciu'],$params );

       $token = json_decode(  json_encode($userToken) ,true);

      return $this->generateToken( $token['data']['access_token'] ,$origin['ciu'], $token['data']['id'],$request->ip() );
    }

    public function generateToken($tokenBearer,$origin,$userId,$ip){
        $token = uniqid();
        TokenReader::create([
            'token'=>$token,
            'created_by'=>$userId,
            'token_access'=>$tokenBearer,
            'expire_time'=>Carbon::now()->addMinutes(5),
            'origin'=>$origin,
            'ip_address'=>$ip
        ]);

        return $token;
    }
}
