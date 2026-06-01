<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 👇 BINA LOGIN WALE USERS KO SAHI ROUTE PAR BHEJNE KA JADOO YAHAN HAI 👇
        $middleware->redirectGuestsTo(fn () => route('admin.login.view'));
        
        // Yahan apna naya middleware alias add karein
        $middleware->alias([
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'time.bound' => \App\Http\Middleware\CheckTimeBoundAccess::class,
            'is_developer' => \App\Http\Middleware\IsDeveloper::class,
        'is_employee' => \App\Http\Middleware\IsEmployee::class,
        ]);

        
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();