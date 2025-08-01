<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class ApiController extends BaseController
{
    /**
     * Returns a standardized JSON response
     *
     * @param mixed|null $data
     * @param string $message
     * @param int $code
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function render($data = null, string $message = '', int $code = 200, array $errors = null): JsonResponse
    {
        $response = [
            'success' => $code >= 200 && $code < 300,
            'message' => $message,
            'data'    => $data,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $code);
    }

}
