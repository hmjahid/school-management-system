<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandardizeApiResponse
{
    protected const PAGE_META_KEYS = ['current_page', 'per_page', 'total', 'last_page'];

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! ($response instanceof JsonResponse)) {
            return $response;
        }

        if (! $this->shouldWrap($request)) {
            return $response;
        }

        $data = $response->getData(true);

        if (isset($data['success']) && is_bool($data['success'])) {
            // Already enveloped — normalize missing message to keep shape uniform.
            $data['message'] = $data['message'] ?? (($data['success'] ? 'OK' : 'Request failed.'));
            if (! isset($data['request_id']) && $request->header('X-Request-ID')) {
                $data['request_id'] = $request->header('X-Request-ID');
            }

            return response()->json($data, $response->getStatusCode(), $response->headers->all());
        }

        $payload = $this->buildPayload($data);

        if ($requestId = $request->header('X-Request-ID')) {
            $payload['request_id'] = $requestId;
        }

        return response()->json($payload, $response->getStatusCode(), $response->headers->all());
    }

    protected function shouldWrap(Request $request): bool
    {
        return $request->is('api/*')
            && ! $request->is('*/webhook/*')
            && ! $request->is('*/callback/*');
    }

    protected function buildPayload(array $data): array
    {
        // Direct paginator serialization (response()->json($paginator)).
        if (isset($data['data'], $data['total'], $data['current_page'], $data['per_page'])) {
            return [
                'success' => true,
                'message' => 'OK',
                'data' => $data['data'],
                'meta' => ['pagination' => array_intersect_key($data, array_flip(self::PAGE_META_KEYS))],
            ];
        }

        // Any payload carrying a top-level "data" wrapper: single resources
        // ({data, meta}) and ResourceCollections ({data, links, meta}).
        if (isset($data['data'])) {
            $payload = ['success' => true, 'message' => 'OK', 'data' => $data['data']];

            $meta = $data['meta'] ?? [];
            if (is_array($meta)) {
                if (isset($meta['pagination']) && is_array($meta['pagination'])) {
                    $meta['pagination'] = array_intersect_key($meta['pagination'], array_flip(self::PAGE_META_KEYS));
                } elseif (isset($meta['current_page'], $meta['total'], $meta['per_page'])) {
                    $meta = ['pagination' => array_intersect_key($meta, array_flip(self::PAGE_META_KEYS))];
                }
            }
            if ($meta !== []) {
                $payload['meta'] = $meta;
            }

            return $payload;
        }

        return [
            'success' => true,
            'message' => 'OK',
            'data' => $data,
        ];
    }
}
