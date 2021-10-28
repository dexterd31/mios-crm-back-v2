<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponse
{

    public function successResponse($data, $statusCode = Response::HTTP_OK)
    {
        return response()->json(['data' => $data], $statusCode);
    }

    public function errorResponse($errorMessage, $statusCode)
    {
        return response()->json(['error' => $errorMessage, 'error_code' => $statusCode], $statusCode);
    }

    public function responseTmk($message, $code = 0, $restCode = Response::HTTP_OK): JsonResponse
    {
        return response()->json(['codigo' => $code, 'mensaje' => $message], $restCode);
    }
}
