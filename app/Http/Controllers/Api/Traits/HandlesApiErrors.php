<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

trait HandlesApiErrors
{
    /**
     * Executes a callback function capturing exceptions for centralized handling.
     *
     * @param callable $callback
     * @param string $errorMessage
     * @param Request|null $logContext
     * @param callable|null $onSuccess
     * @param callable|null $onFailure
     * @return JsonResponse
     */
    protected function handleApi(
        callable $callback,
        string $errorMessage = 'Internal Error',
        ?Request $request = null,
        ?callable $onSuccess = null,
        ?callable $onFailure = null
    ): JsonResponse {
        try {
            $result = $callback();

            if ($onSuccess) {
                $onSuccess($result);
            }

            return $result;
        } catch (Throwable $e) {
            //If the Request was passed, we use exceptionError to log
            if ($request) {
                Log::exceptionError($request, $e, $errorMessage);
            } else {
                // If there is no Request, we make a basic log
                Log::record('error', $errorMessage, [
                    'exception' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
            }

            if ($onFailure) {
                $onFailure($e);
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'data' => null,
            ], 500);
        }
    }
}
