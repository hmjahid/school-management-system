<?php

use App\Exceptions\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['api', 'request.id', 'force.json'])
                ->prefix('api/v1')
                ->group(base_path('routes/payments.php'));

            Route::middleware(['api', 'request.id', 'force.json'])
                ->prefix('api/v1')
                ->group(base_path('routes/admissions.php'));

            Route::middleware(['api', 'request.id', 'force.json'])
                ->prefix('api/v1')
                ->group(base_path('routes/notifications.php'));

            Route::middleware(['api', 'request.id', 'force.json'])
                ->prefix('api/v1')
                ->group(base_path('routes/refunds.php'));

            Route::middleware('web')
                ->group(base_path('routes/dashboard.php'));

            Route::middleware(['api', 'request.id', 'force.json', 'auth:sanctum', 'role:admin'])
                ->prefix('api/v1/admin')
                ->group(base_path('routes/admin/notifications.php'));
        },
    )
    ->withProviders([
        \Spatie\Permission\PermissionServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');

        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleFromSession::class,
            \App\Http\Middleware\LogVisitor::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\RequestId::class,
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\CorsMiddleware::class,
        ]);

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
        // TODO: In production set TRUSTED_PROXIES to your load balancer / CDN CIDRs,
        // e.g. TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16

        $middleware->alias([
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'student_guardian' => \App\Http\Middleware\StudentGuardianMiddleware::class,
            'request.id' => \App\Http\Middleware\RequestId::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            return app(ApiExceptionRenderer::class)->render($request, $e);
        });
    })->create();
