<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            throw new ForbiddenException('System admin access required.');
        }

        return $next($request);
    }
}