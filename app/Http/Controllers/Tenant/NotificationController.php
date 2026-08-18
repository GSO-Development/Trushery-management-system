<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the Notification & Facility Expiry Management page for a sub-company.
     */
    public function index(Request $request, string $company_slug): View
    {
        $company = Company::where('slug', $company_slug)->firstOrFail();

        $categoryFilter = $request->query('category', 'all');
        $urgencyFilter  = $request->query('urgency', 'all');
        $search         = trim((string) $request->query('search', ''));

        // Retrieve all alerts for this company (90-day look-ahead)
        $allAlerts = NotificationService::getAlerts($company->id, 90);
        $summary   = NotificationService::getSummary($company->id, 90);

        // Apply filters
        $filteredAlerts = collect($allAlerts)->filter(function ($alert) use ($categoryFilter, $urgencyFilter, $search) {
            // Category filter
            if ($categoryFilter !== 'all' && in_array($categoryFilter, ['fd', 'wc', 'ltl'])) {
                if ($alert['category_key'] !== $categoryFilter) return false;
            }

            // Urgency filter
            if ($urgencyFilter === 'overdue' && ($alert['days_left'] ?? 0) >= 0) return false;
            if ($urgencyFilter === 'critical' && (($alert['days_left'] ?? 0) < 0 || ($alert['days_left'] ?? 0) > 7)) return false;
            if ($urgencyFilter === 'upcoming' && ($alert['days_left'] ?? 0) <= 7) return false;

            // Search
            if ($search !== '') {
                $s = strtolower($search);
                if (
                    ! str_contains(strtolower($alert['title'] ?? ''), $s) &&
                    ! str_contains(strtolower($alert['message'] ?? ''), $s) &&
                    ! str_contains(strtolower($alert['bank_name'] ?? ''), $s) &&
                    ! str_contains(strtolower($alert['reference'] ?? ''), $s)
                ) {
                    return false;
                }
            }

            return true;
        })->values()->all();

        return view('tenant.notifications', compact(
            'company',
            'allAlerts',
            'filteredAlerts',
            'summary',
            'categoryFilter',
            'urgencyFilter',
            'search'
        ));
    }

    /**
     * Dispatch email alerts to authorized group users for this sub-company.
     */
    public function dispatchEmails(Request $request, string $company_slug)
    {
        $company = Company::where('slug', $company_slug)->firstOrFail();
        $targetAlertId = $request->input('alert_id');

        $result = NotificationService::dispatchAlertEmails($company->id, $targetAlertId);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }
}