<?php

namespace App\Http\Controllers\TmkPymes;

use App\Http\Controllers\Controller;
use App\Services\TmkPymesServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class TmkPymesController extends Controller
{
    private $tmkPymesServices;
    private $formId;
    public function __construct(TmkPymesServices $pymesServices)
    {
        $this->tmkPymesServices = $pymesServices;
        $this->formId = env('TMK_PYMES_WB_FORM_ID', 2);
    }

    /**
     * Metodo que retorna el token para que el cliente claro
     * @return JsonResponse
     */
    public function generateToken(): JsonResponse
    {
        try {
            $payload = JWTFactory::customClaims(['rrhh_id' => 1, 'form_id' => $this->formId]);
            return response()->json(['clientToken' => 'Bearer '. JWTAuth::encode($payload->make())->get() ?? '']);
        } catch (\Throwable $th) {
            Log::error("Code: {$th->getCode()}, Message: {$th->getMessage()}, File: {$th->getFile()}, Line: {$th->getLine()}");
            return $this->responseTmk($th->getMessage(), $th->getCode());
        }
    }

    /**
     * Metodo encargado de realizar el guardado del lead de tmk
     * @param Request $request
     * @return JsonResponse|void
     */
    public function store(Request $request)
    {
        //$this->middleware('auth');
        try {
            $validator = validator($request->all(), [
                'razon_social' => 'required',
                'ciudad' => 'required',
                'telefono' => 'required',
                'optin' => 'required',
            ]);
            if ($validator->fails()) return $this->responseTmk(implode(", ", $validator->errors()->all()), -1);

            $this->tmkPymesServices->setLeadFields($request->only($this->tmkPymesServices->leadColumns()));
            $this->tmkPymesServices->setAccount($this->formId);

        } catch (\Throwable $th) {
            Log::error("Code: {$th->getCode()}, Message: {$th->getMessage()}, File: {$th->getFile()}, Line: {$th->getLine()}");
            return $this->responseTmk($th->getMessage(), -1);
        }
    }
}
