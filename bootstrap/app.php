<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\ContextGuardMiddleware;
use App\Http\Middleware\SystemAdminOnly;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;



return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )

    /* =========================================================
     * MIDDLEWARE
     * ========================================================= */
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum abilities middleware aliases
        $middleware->alias([
            // Context enforcement
            'system.admin' => SystemAdminOnly::class,
            'context.guard' => ContextGuardMiddleware::class,

            // Sanctum abilities
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })

    /* =========================================================
     * EXCEPTION HANDLING (API-FIRST)
     * ========================================================= */
    ->withExceptions(function (Exceptions $exceptions): void {

        /**
         * ---------------------------------------------------------
         * Standardized API Error Response Formatter
         * ---------------------------------------------------------
         * Ensures ALL API errors follow the same structure.
         */
        $apiError = function (string|array $errors, int $status) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'errors' => is_string($errors)
                    ? ['message' => $errors]
                    : $errors,
            ], $status);
        };

        /**
         * ---------------------------------------------------------
         * 1️⃣ Custom Application Exceptions (Highest Priority)
         * ---------------------------------------------------------
         * All business-layer exceptions extending ApiException
         * are handled here.
         *
         * Example:
         * - ConflictException
         * - NotFoundException
         * - ForbiddenException
         */
        $exceptions->render(function (ApiException $e, Request $request) use ($apiError) {
            if (! $request->expectsJson()) {
                return null;
            }

            return $apiError($e->getErrors(), $e->getStatus());
        });

        /**
         * ---------------------------------------------------------
         * 2️⃣ Database Exceptions (Infrastructure Layer)
         * ---------------------------------------------------------
         * Handles DB-level integrity violations like:
         * - Duplicate unique key (409)
         *
         * IMPORTANT:
         * We NEVER expose raw SQL errors to clients.
         */
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) use ($apiError) {

            if (! $request->expectsJson()) {
                return null;
            }

            // MySQL duplicate entry error code
            if (($e->errorInfo[1] ?? null) === 1062) {

                // Optional: You can replace this with
                // DatabaseExceptionTranslator::translate($e)
                return $apiError([
                    'message' => 'Duplicate record already exists.',
                ], 409);
            }

            // Log unexpected DB issues
            report($e);

            return $apiError(app()->isLocal()? $e->getMessage():'Database error.', 500);
        });

        /**
         * ---------------------------------------------------------
         * 3️⃣ Authentication Exception (401)
         * ---------------------------------------------------------
         */
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) use ($apiError) {
            if ($request->expectsJson()) {
                return $apiError('Unauthenticated.', 401);
            }
        });

        /**
         * ---------------------------------------------------------
         * 4️⃣ Authorization Exception (403)
         * ---------------------------------------------------------
         */
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) use ($apiError) {
            if ($request->expectsJson()) {
                return $apiError('Forbidden.', 403);
            }
        });

        /**
         * ---------------------------------------------------------
         * 5️⃣ Validation Exception (422)
         * ---------------------------------------------------------
         * Automatically triggered by FormRequest.
         */
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, Request $request) use ($apiError) {
            if ($request->expectsJson()) {
                return $apiError([
                    'message' => 'Validation failed.',
                    'fields' => $e->errors(),
                ], 422);
            }
        });

        /**
         * ---------------------------------------------------------
         * 6️⃣ HTTP Exceptions (404 / 405)
         * ---------------------------------------------------------
         */
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) use ($apiError) {
            if ($request->expectsJson()) {
                return $apiError('Resource not found.', 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, Request $request) use ($apiError) {
            if ($request->expectsJson()) {
                return $apiError(app()->isLocal()? $e->getMessage():'Method not allowed for this endpoint.', 405);
            }
        });

        /**
         * ---------------------------------------------------------
         * 7️⃣ Final Fallback (500 – Unhandled Errors)
         * ---------------------------------------------------------
         * This is the last safety net.
         * We NEVER expose internal error messages.
         */
        $exceptions->render(function (\Throwable $e, Request $request) use ($apiError) {

            if (! $request->expectsJson()) {
                return null;
            }

            // Log the actual error internally
            report($e);

            return $apiError(app()->isLocal()? $e->getMessage(): 'Internal server error.', 500);
        });

        /**
         * ---------------------------------------------------------
         * Sensitive Inputs Protection
         * ---------------------------------------------------------
         * Prevents these fields from being flashed to session.
         */
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })

    ->create();
