<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CeoAccess
{
    /**
     * Handle an incoming request.
     * Grants access to users with is_ceo = true, admin, or assigned to a Group-type Access Group.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->is_ceo && ! ($user->group && $user->group->isGroup()) && ! $user->is_admin)) {
            abort(403, 'CEO or Multi-Company Group access required.');
        }

        return $next($request);
    }
}