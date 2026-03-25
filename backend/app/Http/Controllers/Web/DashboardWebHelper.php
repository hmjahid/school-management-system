<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class DashboardWebHelper
{
    public static function fromJsonResponse(
        JsonResponse|Response $response,
        int $successCode,
        string $route,
        string $message,
        array $routeParams = []
    ): ?RedirectResponse {
        if (! $response instanceof JsonResponse) {
            return null;
        }
        if ($response->getStatusCode() !== $successCode) {
            return null;
        }

        return redirect()->route($route, $routeParams)->with('status', __($message));
    }

    public static function jsonErrorMessage(JsonResponse $response): ?string
    {
        if ($response->getStatusCode() < 400) {
            return null;
        }
        $data = $response->getData(true);

        return is_array($data) ? ($data['message'] ?? null) : null;
    }
}
