<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        if ($requestId = request()->header('X-Request-ID')) {
            $payload['request_id'] = $requestId;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function paginated(
        AbstractPaginator $paginator,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return $this->success($paginator->items(), $message, 200, array_merge([
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], $meta));
    }

    protected function error(
        string $message,
        int $status = 400,
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
