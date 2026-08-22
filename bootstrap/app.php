<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A 422 answer must name the offending fields: the caller only logs
        // the `message`, so "validation.required" alone is undebuggable.
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $missing = [];

            foreach ($exception->validator->failed() as $field => $rules) {
                if (array_key_exists('Required', $rules) || array_key_exists('Present', $rules)) {
                    $missing[] = $field;
                }
            }

            $invalid = array_values(array_diff(array_keys($exception->errors()), $missing));

            $parts = [];

            if ($missing !== []) {
                $parts[] = 'missing required fields: '.implode(', ', $missing);
            }

            if ($invalid !== []) {
                $parts[] = 'invalid fields: '.implode(', ', $invalid);
            }

            return response()->json([
                'message' => 'Validation failed'.($parts === [] ? '.' : ' - '.implode('; ', $parts).'.'),
                'missing_fields' => $missing,
                'invalid_fields' => $invalid,
                'errors' => $exception->errors(),
            ], $exception->status);
        });
    })->create();
