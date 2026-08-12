<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantAccess Middleware
 * ─────────────────────────────────────────────────────────────────────────────
 * Guards all tenant (sub-company) portal routes.
 * Runs checks in order:
 *   1. User is authenticated
 *   2. User is NOT an admin (admins use /admin portal)
 *   3. CEO users are redirected to /ceo/dashboard
 *   4. User belongs to the company in {company_slug}
 *   5. User has a group assigned
 *   6. User's group has at least one nav permission
 *   7. User's group has permission for this specific page
 */
class TenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Must be authenticated
        if (! $user) {
            return redirect()->route('login');
        }

        // 2. Admins use /admin — not tenant routes
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        // 3. CEO users have their own dashboard
        if ($user->is_ceo) {
            return redirect()->route('ceo.dashboard');
        }

        // 4. Verify company slug matches user's assigned company
        $companySlug = $request->route('company_slug');

        if (! $companySlug) {
            abort(403, 'Invalid tenant route: company slug missing.');
        }

        if (! $user->company || $user->company->slug !== $companySlug) {
            abort(403, 'Access Forbidden: You do not belong to this company portal.');
        }

        // 5. User must have a group
        if (! $user->group) {
            return redirect()->route('login')
                ->with('error', 'Your account has no group assigned. Please contact your administrator.');
        }

        // 6. Group must have at least one nav permission
        if (empty($user->group->getNavKeys())) {
            abort(403, 'Access Forbidden: Your group has no assigned permissions.');
        }

        // 7. Check page-level permission from the URL path
        //    e.g. /health/rate-management → page slug = 'rate-management' → nav key = 'rate_management'
        $pageSlug = $request->segment(2);
        if ($pageSlug) {
            $navKey = str_replace('-', '_', $pageSlug);
            if (! $user->group->hasNavPermission($navKey)) {
                abort(403, 'Access Forbidden: Your group does not have permission to view this page.');
            }
        }

        return $next($request);
    }
}
