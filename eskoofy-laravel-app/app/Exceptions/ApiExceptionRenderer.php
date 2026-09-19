<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionRenderer
{
    public function render(Request $request, Throwable $e): ?JsonResponse
    {
        if (! $this->shouldRenderAsApi($request)) {
            return null;
        }

        if ($e instanceof ApiException) {
            return $this->json(
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getErrors(),
                $e->getErrorCode()
            );
        }

        if ($e instanceof ValidationException) {
            return $this->json(
                'Validation failed',
                422,
                $e->errors(),
                'VALIDATION_ERROR'
            );
        }

        if ($e instanceof AuthenticationException) {
            return $this->json('Unauthenticated.', 401, null, 'UNAUTHENTICATED');
        }

        if ($e instanceof AuthorizationException) {
            return $this->json('This action is unauthorized.', 403, null, 'FORBIDDEN');
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->json('Resource not found.', 404, null, 'NOT_FOUND');
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->json('Endpoint not found.', 404, null, 'NOT_FOUND');
        }

        if ($e instanceof HttpException) {
            return $this->json(
                $e->getMessage() ?: 'Request failed.',
                $e->getStatusCode(),
                null,
                'HTTP_ERROR'
            );
        }

        report($e);

        return $this->json(
            config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            500,
            config('app.debug') ? ['exception' => class_basename($e)] : null,
            'SERVER_ERROR'
        );
    }

    protected function shouldRenderAsApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    protected function json(
        string $message,
        int $status,
        ?array $errors = null,
        ?string $code = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($code !== null) {
            $payload['code'] = $code;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        if ($requestId = request()->header('X-Request-ID')) {
            $payload['request_id'] = $requestId;
        }

        return response()->json($payload, $status);
    }
}
