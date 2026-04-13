<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use App\Guards\HierarchyScopeGuard;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class ContextGuardMiddleware
{
    public function __construct(
        protected HierarchyScopeGuard $hierarchyGuard
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException();
        }

        /*
        |--------------------------------------------------------------------------
        | 🔥 SYSTEM ADMIN BYPASS
        |--------------------------------------------------------------------------
        | Admin operates at platform level.
        | No context required.
        | No hierarchy validation required.
        */

        if ($user->is_admin) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | BUSINESS USER: CONTEXT REQUIRED
        |--------------------------------------------------------------------------
        */

        $context = $user->activeContext();

        if (! $context) {
            throw new ForbiddenException('No active context selected.');
        }

        /*
        |--------------------------------------------------------------------------
        | STRUCTURAL HIERARCHY VALIDATION
        |--------------------------------------------------------------------------
        */

        $this->hierarchyGuard->enforce($context);

        return $next($request);
    }
}