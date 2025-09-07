<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use App\Exceptions\BusinessRuleException;

/**
 * Trait HandlesApiErrors
 *
 * Provides centralized error handling for API controllers, following
 * the RFC 7807 "Problem Details for HTTP APIs" specification.
 *
 * Instead of returning inconsistent error JSON structures, this trait
 * ensures that all errors are returned in a predictable, standardized
 * format (problem+json).
 */
trait HandlesApiErrors
{
    /**
     * Executes a callback with centralized error handling.
     * If an exception occurs, it will be logged and a proper
     * RFC 7807 problem response will be returned.
     *
     * @param callable $callback
     * @param string $errorMessage
     * @param Request|null $request
     * @param callable|null $onSuccess
     * @param callable|null $onFailur
     *
     * @return JsonResponse
     */
    protected function handleApi(
        callable $callback,
        string $errorMessage = 'Internal Server Error',
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
            // Map exception type to an HTTP status code
            $status = $this->mapExceptionToStatus($e);

            // Log exception (with request context if available)
            if ($request) {
                Log::exceptionError($request, $e, $errorMessage);
            } else {
                Log::record('error', $errorMessage, [
                    'exception' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
            }

            if ($onFailure) {
                $onFailure($e);
            }

            // Return standardized RFC 7807 problem response
            return $this->problemResponse(
                type: env("L5_SWAGGER_CONST_HOST") . DS . "errors/$status",
                title: Response::$statusTexts[$status] ?? 'Error',
                status: $status,
                detail: $e->getMessage() ?: $errorMessage,
                instance: $request?->path(),
                code: $this->mapExceptionToCode($e),
                category: $this->mapExceptionToCategory($e)
            );
        }
    }

    /**
     * Build and return a JSON response following RFC 7807 "Problem Details".
     *
     * @param string $type
     * @param string $title
     * @param int $status
     * @param string|null $detail
     * @param string|null $instance
     * @param string|null $code
     * @param string|null $category
     *
     * @return JsonResponse
     */
    protected function problemResponse(
        string $type,
        string $title,
        int $status,
        ?string $detail = null,
        ?string $instance = null,
        ?string $code = null,
        ?string $category = null
    ): JsonResponse {
        $problem = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $instance ?? request()->path(),
        ];

        if ($code) {
            $problem['code'] = $code;
        }

        if ($category) {
            $problem['category'] = $category;
        }

        return response()->json($problem, $status);
    }

    /**
     * Map common Laravel exceptions to the appropriate HTTP status code.
     *
     * @param Throwable $e The exception to evaluate.
     *
     * @return int The corresponding HTTP status code.
     */
    protected function mapExceptionToStatus(Throwable $e): int
    {
        return match (true) {
            $e instanceof BusinessRuleException     => $e->getStatus(),
            $e instanceof ModelNotFoundException    => 404, // Resource not found
            $e instanceof AuthenticationException   => 401, // Unauthenticated
            $e instanceof AuthorizationException    => 403, // Forbidden
            $e instanceof ValidationException       => 422, // Validation error
            $e instanceof HttpResponseException     => $e->getResponse()->getStatusCode(),
            default                                 => 500, // Internal server error
        };
    }

    /**
     * Map exceptions to internal business codes.
     * These codes are useful for the client
     * to identify the exact type of error without relying solely
     * on the HTTP code.
     *
     * @param Throwable $e
     * @return string|null
     */
    protected function mapExceptionToCode(Throwable $e): ?string
    {
        if ($e instanceof BusinessRuleException && $e->getErrorCode()) {
            return $e->getErrorCode();
        }

        return match (true) {
            $e instanceof ModelNotFoundException    => 'RESOURCE_NOT_FOUND',
            $e instanceof AuthenticationException   => 'AUTH_REQUIRED',
            $e instanceof AuthorizationException    => 'ACCESS_DENIED',
            $e instanceof ValidationException       => 'VALIDATION_FAILED',
            default                                 => 'INTERNAL_ERROR',
        };
    }

    /**
     * Map exceptions to a logical category.
     * Categories help clients group errors (e.g. TASKS, SUBTASKS, CALENDAR).
     *
     * @param Throwable $e The exception to evaluate
     * @return string|null Category string if available
     */
    protected function mapExceptionToCategory(Throwable $e): ?string
    {
        if ($e instanceof BusinessRuleException && $e->getCategory()) {
            return $e->getCategory();
        }

        return null;
    }
}
