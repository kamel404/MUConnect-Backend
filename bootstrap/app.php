<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\StartSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// protected $middlewareGroups = [
//     'web' => [
//         EnsureFrontendRequestsAreStateful::class,
//         \Illuminate\Session\Middleware\StartSession::class,
//         \Illuminate\View\Middleware\ShareErrorsFromSession::class,
//         \Illuminate\Routing\Middleware\SubstituteBindings::class,
//     ],
//     'api' => [
//         'throttle:api',
//         \Illuminate\Routing\Middleware\SubstituteBindings::class,
//     ],
// ];



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->append(StartSession::class);
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

