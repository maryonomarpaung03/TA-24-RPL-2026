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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
        $middleware->web(append: [
            \App\Http\Middleware\EnforceIdleTimeout::class,
        ]);
        $middleware->alias([
            'project.pjbl' => \App\Http\Middleware\EnsureProjectPjblAccess::class,
            'stage.waterfall' => \App\Http\Middleware\EnsureStageWaterfall::class,
            'final.submitted' => \App\Http\Middleware\EnsureFinalSubmitted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
