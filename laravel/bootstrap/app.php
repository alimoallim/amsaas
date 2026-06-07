<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetTenantContext;

return Application::configure(
    basePath: dirname(__DIR__)
)
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
->withMiddleware(function (Middleware $middleware) {
    /*
    |--------------------------------------------------------------------------
    | Global Middleware Registration
    |--------------------------------------------------------------------------
    | We append the tenant context setter to both web and api groups to ensure
    | that every request, regardless of entry point, initializes the 
    | Multi-Tenancy manager before the controller logic fires.
    */
    $middleware->web(append: [
        SetTenantContext::class,
    ]);

    $middleware->api(append: [
        SetTenantContext::class,
    ]);
})
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(
        function (
            Illuminate\Auth\AuthenticationException $e,
            Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        }
    );

    $exceptions->render(
        function (
            App\Exceptions\BusinessRuleException $e,
            Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->errorCode,
                ], 422);
            }
        }
    );

    $exceptions->render(
        function (
            Illuminate\Validation\ValidationException $e,
            Illuminate\Http\Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }
        }
    );
})
->create();