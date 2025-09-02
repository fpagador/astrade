<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\HandlesApiErrors;
use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class ApiController extends BaseController
{
    use HandlesApiErrors;

    /**
     * Returns a standardized JSON response
     *
     * @param mixed|null $data
     * @param string $message
     * @return JsonResponse
     *
     * @OA\Schema(
     *     schema="ApiResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Operation successful"),
     *     @OA\Property(property="data", type="object")
     * )
     */
    protected function render($data = null, string $message = ''): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
