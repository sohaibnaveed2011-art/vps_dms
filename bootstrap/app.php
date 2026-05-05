<?php

use App\Exceptions\ApiException;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Middleware\ContextGuardMiddleware;
use App\Http\Middleware\SystemAdminOnly;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
         */
        $apiError = function (string|array $errors, int $status) {
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'errors' => is_string($errors) ? ['message' => $errors] : $errors,
            ], $status);
        };

        /**
         * ---------------------------------------------------------
         * 1️⃣ CUSTOM API EXCEPTIONS (Business Layer)
         * ---------------------------------------------------------
         * Catches: ForbiddenException, UnauthorizedException, 
         *          ConflictException, NotFoundException, etc.
         */
        $exceptions->render(function (ApiException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            return $apiError(
                $e->getErrors(), 
                $e->getStatus()
            );
        });

        /**
         * ---------------------------------------------------------
         * 2️⃣ ACCESS DENIED HTTP EXCEPTION (403)
         * ---------------------------------------------------------
         * Catches: AccessDeniedHttpException from AuthService
         * Used for: Account disabled, no role assigned, no context
         */
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            return $apiError([
                'message' => $e->getMessage(),
            ], 403);
        });

        /**
         * ---------------------------------------------------------
         * 3️⃣ VALIDATION EXCEPTION (422)
         * ---------------------------------------------------------
         * Catches: ValidationException::withMessages()
         * Used for: Invalid credentials, validation errors
         */
        $exceptions->render(function (ValidationException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            // Return in the format expected by frontend
            return response()->json([
                'success' => false,
                'data' => null,
                'meta' => null,
                'errors' => $e->errors(), // Direct errors from withMessages()
            ], 422);
        });

        /**
         * ---------------------------------------------------------
         * 4️⃣ LARAVEL AUTHENTICATION EXCEPTION (401)
         * ---------------------------------------------------------
         * Catches: AuthenticationException from Laravel
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            return $apiError([
                'message' => 'Unauthenticated. Please login.',
            ], 401);
        });

        /**
         * ---------------------------------------------------------
         * 5️⃣ LARAVEL AUTHORIZATION EXCEPTION (403)
         * ---------------------------------------------------------
         * Catches: AuthorizationException from Gates/Policies
         */
        $exceptions->render(function (AuthorizationException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            return $apiError([
                'message' => $e->getMessage() ?: 'Forbidden. You don\'t have permission to perform this action.',
            ], 403);
        });

        /**
         * ---------------------------------------------------------
         * 6️⃣ DATABASE QUERY EXCEPTIONS
         * ---------------------------------------------------------
         * Catches: QueryException (duplicate entries, etc.)
         */
        $exceptions->render(function (QueryException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }

            // MySQL duplicate entry error (1062)
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $apiError([
                    'message' => 'Duplicate record already exists.',
                    'details' => 'A record with this information already exists in the system.',
                ], 409);
            }

            // Foreign key constraint error (1451 or 1452)
            if (($e->errorInfo[1] ?? null) === 1451 || ($e->errorInfo[1] ?? null) === 1452) {
                return $apiError([
                    'message' => 'Cannot perform this operation due to existing relationships.',
                    'details' => 'This record is being used by other records in the system.',
                ], 409);
            }

            // Log unexpected database errors
            \Illuminate\Support\Facades\Log::error('Database error: ' . $e->getMessage(), [
                'sql' => $e->getSql() ?? 'Unknown',
                'bindings' => $e->getBindings() ?? [],
                'code' => $e->getCode(),
            ]);

            return $apiError(
                app()->isLocal() ? $e->getMessage() : 'Database error occurred. Please try again later.',
                500
            );
        });

        /**
         * ---------------------------------------------------------
         * 7️⃣ NOT FOUND HTTP EXCEPTION (404)
         * ---------------------------------------------------------
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            return $apiError([
                'message' => 'Resource not found.',
                'details' => 'The requested resource does not exist.',
            ], 404);
        });

        /**
         * ---------------------------------------------------------
         * 8️⃣ METHOD NOT ALLOWED EXCEPTION (405)
         * ---------------------------------------------------------
         */
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }
            
            return $apiError([
                'message' => 'Method not allowed.',
                'details' => app()->isLocal() ? $e->getMessage() : 'The HTTP method used is not supported for this endpoint.',
            ], 405);
        });

        /**
         * ---------------------------------------------------------
         * 9️⃣ FINAL FALLBACK - UNHANDLED EXCEPTIONS (500)
         * ---------------------------------------------------------
         * This catches ANY exception not handled above
         */
        $exceptions->render(function (\Throwable $e, Request $request) use ($apiError) {
            if (!$request->expectsJson()) {
                return null;
            }

            // Log the full error for debugging (always log, even in production)
            \Illuminate\Support\Facades\Log::error('Unhandled exception: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'input' => $request->except(['password', 'password_confirmation', 'current_password']),
                'user_id' => $request->user()?->id,
            ]);

            // Return generic error for production, detailed for local
            return $apiError(
                app()->isLocal() 
                    ? [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'type' => get_class($e),
                    ]
                    : [
                        'message' => 'An unexpected server error occurred. Please try again later.',
                        'reference' => 'ERR_' . uniqid(), // For tracking in logs
                    ],
                500
            );
        });

        /**
         * ---------------------------------------------------------
         * SENSITIVE INPUTS PROTECTION
         * ---------------------------------------------------------
         * Prevents these fields from being flashed to session
         */
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
        ]);
    })

    ->create();