<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Middleware\AuthenticateAdmin;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app =  Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // Optional, if you're using API routes
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->append(AuthenticateUser::class);
        // $middleware->append(AuthenticateAdmin::class);
        $middleware->append(\App\Http\Middleware\ApiResponse::class);


    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    });






return $app->create();
