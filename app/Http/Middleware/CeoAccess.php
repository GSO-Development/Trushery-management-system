<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CeoAccess
{
    /**
     * Handle an incoming request.
     * Grants access only to users with is_ceo = true.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_ceo) {
            abort(403, 'CEO access required.');
        }

        return $next($request);
    }
}
