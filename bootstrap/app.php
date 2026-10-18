<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Di Vercel (dan hosting lain yang memakai load balancer), request
        // HTTPS dari pengunjung diteruskan ke aplikasi sebagai HTTP biasa.
        // Tanpa ini Laravel mengira situsnya HTTP, sehingga link dan
        // form yang dibuatnya ikut memakai http:// dan diblokir peramban.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
