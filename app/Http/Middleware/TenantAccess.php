<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // 1. Must be authenticated
        if (! $user) {
            return redirect()->route('login');
        }

        $companySlug = $request->route('company_slug');
        if (! $companySlug) {
            abort(403, 'Invalid tenant route: company slug missing.');
        }

        $company = Company::where('slug', $companySlug)->first();
        if (! $company) {
            abort(404, 'Sub-Company not found.');
        }

        // 2. Admins use /admin - redirect them to admin or group portal
        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        // 3. CEO or Group-level multi-company users:
        // They must NOT access internal tenant operational forms. Redirect them to the Executive View in /group/company/{slug}
        if ($user->isCeoOrGroupUser()) {
            return redirect()->route('group.company.dashboard', $companySlug);
        }

        // 4. Regular sub-company tenant users: verify assigned company
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

        // 7. Check page-level permission from the URL path (profile and notifications are universally available)
        $pageSlug = $request->segment(2);
        if ($pageSlug && $pageSlug !== 'profile' && $pageSlug !== 'notifications') {
            $navKey = str_replace('-', '_', $pageSlug);
            if (! $user->group->hasNavPermission($navKey)) {
                abort(403, 'Access Forbidden: Your group does not have permission to view this page.');
            }
        }

        return $next($request);
    }
}