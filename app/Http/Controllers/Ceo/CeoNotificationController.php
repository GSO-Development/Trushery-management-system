<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeoNotificationController extends Controller
{
    /**
     * Display consolidated notifications for all companies in the user's group.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Determine accessible companies
        if ($user->is_admin) {
            $accessibleCompanies = Company::where('slug', '!=', 'admin')->orderBy('name')->get();
        } elseif ($user->group && $user->group->isGroup() && ! empty($user->group->company_ids)) {
            $accessibleCompanies = Company::whereIn('id', $user->group->company_ids)->orderBy('name')->get();
        } else {
            $accessibleCompanies = $user->ceoCompanies()->orderBy('name')->get();
            if ($accessibleCompanies->isEmpty()) {
                $accessibleCompanies = Company::where('slug', '!=', 'admin')->orderBy('name')->get();
            }
        }

        $accessibleIds = $accessibleCompanies->pluck('id')->toArray();

        // 2. Filters
        $categoryFilter  = $request->query('category', 'all');
        $urgencyFilter   = $request->query('urgency', 'all');
        $companyFilter   = $request->query('company_id', 'all');
        $search          = trim($request->query('search', ''));

        // 3. Fetch all alerts (90-day look-ahead) for all accessible companies
        $allAlerts = NotificationService::getAlerts(null, 90);

        // Filter to only accessible companies
        $allAlerts = array_values(array_filter($allAlerts, function ($a) use ($accessibleIds) {
            $compId = $a['company_id'] ?? null;
            return $compId === null || in_array($compId, $accessibleIds);
        }));

        // 4. Apply filters
        $filteredAlerts = collect($allAlerts);

        if ($companyFilter !== 'all') {
            $filteredAlerts = $filteredAlerts->filter(fn($a) => ($a['company_id'] ?? null) == $companyFilter);
        }

        if ($categoryFilter !== 'all') {
            $filteredAlerts = $filteredAlerts->filter(fn($a) => ($a['category_key'] ?? '') === $categoryFilter);
        }

        if ($urgencyFilter === 'overdue') {
            $filteredAlerts = $filteredAlerts->filter(fn($a) => ($a['days_left'] ?? 0) < 0);
        } elseif ($urgencyFilter === 'critical') {
            $filteredAlerts = $filteredAlerts->filter(fn($a) => ($a['days_left'] ?? 0) >= 0 && ($a['days_left'] ?? 0) <= 7);
        } elseif ($urgencyFilter === 'upcoming') {
            $filteredAlerts = $filteredAlerts->filter(fn($a) => ($a['days_left'] ?? 0) > 7);
        }

        if ($search !== '') {
            $s = strtolower($search);
            $filteredAlerts = $filteredAlerts->filter(fn($a) =>
                str_contains(strtolower($a['title'] ?? ''), $s)
                || str_contains(strtolower($a['message'] ?? ''), $s)
                || str_contains(strtolower($a['bank_name'] ?? ''), $s)
                || str_contains(strtolower($a['reference'] ?? ''), $s)
                || str_contains(strtolower($a['company_name'] ?? ''), $s)
            );
        }

        // 5. Summary counts
        $totalCount    = count($allAlerts);
        $overdueCount  = collect($allAlerts)->filter(fn($a) => ($a['days_left'] ?? 0) < 0)->count();
        $criticalCount = collect($allAlerts)->filter(fn($a) => ($a['days_left'] ?? 0) >= 0 && ($a['days_left'] ?? 0) <= 7)->count();
        $upcomingCount = collect($allAlerts)->filter(fn($a) => ($a['days_left'] ?? 0) > 7)->count();
        $overdueExposure = collect($allAlerts)->filter(fn($a) => ($a['days_left'] ?? 0) < 0)->sum(fn($a) => $a['amount'] ?? 0);

        // 6. Per-company alert counts for sidebar filter buttons
        $companyAlertCounts = [];
        foreach ($accessibleCompanies as $comp) {
            $companyAlertCounts[$comp->id] = collect($allAlerts)->filter(fn($a) => ($a['company_id'] ?? null) == $comp->id)->count();
        }

        return view('ceo.notifications', compact(
            'accessibleCompanies',
            'filteredAlerts',
            'categoryFilter',
            'urgencyFilter',
            'companyFilter',
            'search',
            'totalCount',
            'overdueCount',
            'criticalCount',
            'upcomingCount',
            'overdueExposure',
            'companyAlertCounts'
        ));
    }

    /**
     * Dispatch email alerts to authorized group users across companies.
     */
    public function dispatchEmails(Request $request)
    {
        $targetAlertId = $request->input('alert_id');
        $companyId = $request->input('company_id') ? (int) $request->input('company_id') : null;

        $result = NotificationService::dispatchAlertEmails($companyId, $targetAlertId);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}