<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FixedDeposit;
use App\Models\Group;
use App\Models\LongTermLoan;
use App\Models\NotificationDispatch;
use App\Models\User;
use App\Models\WorkingCapitalLoan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Get upcoming alerts for a specific company (or all companies if $companyId is null / CEO mode).
     * Alerts include FDs, Long Term Loans, and Working Capital Loans due/maturing within $daysAhead (default 30 days).
     *
     * @param int|null $companyId
     * @param int $daysAhead
     * @return array
     */
    public static function getAlerts(?int $companyId = null, int $daysAhead = 30): array
    {
        $alerts = [];
        $today = Carbon::today();
        $targetDate = Carbon::today()->addDays($daysAhead);

        // 1. Fixed Deposits Maturing Soon or Overdue
        $fdQuery = FixedDeposit::with(['company', 'bank'])->where('is_active', true);
        if ($companyId) {
            $fdQuery->where('company_id', $companyId);
        }

        $fds = $fdQuery->whereNotNull('maturity_date')
            ->where('maturity_date', '<=', $targetDate)
            ->get();

        foreach ($fds as $fd) {
            $daysLeft = (int) $today->diffInDays($fd->maturity_date, false);
            $companyName = $fd->company->name ?? 'Sub Company';
            $bankName = $fd->bank->name ?? 'Bank';
            $bankCode = $fd->bank->bank_code ?? 'BNK';
            $amountRaw = (float) $fd->amount;
            $amountFormatted = number_format($amountRaw, 2);
            $slug = $fd->company->slug ?? '';

            if ($daysLeft < 0) {
                $alerts[] = [
                    'id'           => 'fd-' . $fd->id,
                    'type'         => 'danger',
                    'status_label' => 'Overdue',
                    'category'     => 'Fixed Deposit',
                    'category_key' => 'fd',
                    'title'        => 'Fixed Deposit Matured / Overdue',
                    'message'      => "Fixed Deposit of {$fd->currency} {$amountFormatted} at {$bankName} matured " . abs($daysLeft) . " day(s) ago (" . $fd->maturity_date->format('Y-m-d') . "). Action required for settlement or renewal.",
                    'url'          => url("/{$slug}/fixed-deposits"),
                    'company_name' => $companyName,
                    'bank_name'    => $bankName,
                    'bank_code'    => $bankCode,
                    'reference'    => $fd->fd_number ?? ('FD #' . $fd->id),
                    'amount'       => $amountRaw,
                    'currency'     => $fd->currency ?? 'LKR',
                    'date'         => $fd->maturity_date->format('Y-m-d'),
                    'days_left'    => $daysLeft,
                    'icon'         => 'fd',
                    'is_pledged'   => !empty($fd->pledged_details),
                    'company_id'   => $fd->company_id,
                    'company_slug' => $slug,
                ];
            } else {
                $alerts[] = [
                    'id'           => 'fd-' . $fd->id,
                    'type'         => $daysLeft <= 7 ? 'warning' : 'info',
                    'status_label' => $daysLeft <= 7 ? 'Critical (<7 Days)' : 'Upcoming',
                    'category'     => 'Fixed Deposit',
                    'category_key' => 'fd',
                    'title'        => 'Fixed Deposit Maturing Soon',
                    'message'      => "Fixed Deposit of {$fd->currency} {$amountFormatted} at {$bankName} will mature in {$daysLeft} day(s) on " . $fd->maturity_date->format('Y-m-d') . ".",
                    'url'          => url("/{$slug}/fixed-deposits"),
                    'company_name' => $companyName,
                    'bank_name'    => $bankName,
                    'bank_code'    => $bankCode,
                    'reference'    => $fd->fd_number ?? ('FD #' . $fd->id),
                    'amount'       => $amountRaw,
                    'currency'     => $fd->currency ?? 'LKR',
                    'date'         => $fd->maturity_date->format('Y-m-d'),
                    'days_left'    => $daysLeft,
                    'icon'         => 'fd',
                    'is_pledged'   => !empty($fd->pledged_details),
                    'company_id'   => $fd->company_id,
                    'company_slug' => $slug,
                ];
            }
        }

        // 2. Working Capital Loans Settlement Due Soon or Overdue
        $wcQuery = WorkingCapitalLoan::with(['company', 'bank'])->where('is_active', true);
        if ($companyId) {
            $wcQuery->where('company_id', $companyId);
        }

        $wcs = $wcQuery->get();
        foreach ($wcs as $wc) {
            $dueDate = $wc->revised_settlement_date ?? $wc->settlement_date;
            if (! $dueDate) continue;

            $daysLeft = (int) $today->diffInDays($dueDate, false);
            if ($daysLeft <= $daysAhead) {
                $companyName = $wc->company->name ?? 'Sub Company';
                $bankName = $wc->bank->name ?? 'Bank';
                $bankCode = $wc->bank->bank_code ?? 'BNK';
                $amountRaw = (float) ($wc->outstanding_amount ?: $wc->facility_amount);
                $amountFormatted = number_format($amountRaw, 2);
                $slug = $wc->company->slug ?? '';
                $facType = $wc->loan_type ?? $wc->facility_type ?? 'Working Capital';

                if ($daysLeft < 0) {
                    $alerts[] = [
                        'id'           => 'wc-' . $wc->id,
                        'type'         => 'danger',
                        'status_label' => 'Overdue',
                        'category'     => 'Working Capital',
                        'category_key' => 'wc',
                        'title'        => "{$facType} Settlement Overdue",
                        'message'      => "{$facType} facility of {$wc->currency} {$amountFormatted} at {$bankName} was due " . abs($daysLeft) . " day(s) ago (" . $dueDate->format('Y-m-d') . "). Immediate settlement or extension required.",
                        'url'          => url("/{$slug}/working-capital"),
                        'company_name' => $companyName,
                        'bank_name'    => $bankName,
                        'bank_code'    => $bankCode,
                        'reference'    => $wc->facility_type ?? ('WC #' . $wc->id),
                        'amount'       => $amountRaw,
                        'currency'     => $wc->currency ?? 'LKR',
                        'date'         => $dueDate->format('Y-m-d'),
                        'days_left'    => $daysLeft,
                        'icon'         => 'loan',
                        'is_pledged'   => false,
                        'company_id'   => $wc->company_id,
                        'company_slug' => $slug,
                    ];
                } else {
                    $alerts[] = [
                        'id'           => 'wc-' . $wc->id,
                        'type'         => $daysLeft <= 7 ? 'warning' : 'info',
                        'status_label' => $daysLeft <= 7 ? 'Critical (<7 Days)' : 'Upcoming',
                        'category'     => 'Working Capital',
                        'category_key' => 'wc',
                        'title'        => "{$facType} Settlement Due Soon",
                        'message'      => "{$facType} of {$wc->currency} {$amountFormatted} at {$bankName} due in {$daysLeft} day(s) on " . $dueDate->format('Y-m-d') . ".",
                        'url'          => url("/{$slug}/working-capital"),
                        'company_name' => $companyName,
                        'bank_name'    => $bankName,
                        'bank_code'    => $bankCode,
                        'reference'    => $wc->facility_type ?? ('WC #' . $wc->id),
                        'amount'       => $amountRaw,
                        'currency'     => $wc->currency ?? 'LKR',
                        'date'         => $dueDate->format('Y-m-d'),
                        'days_left'    => $daysLeft,
                        'icon'         => 'loan',
                        'is_pledged'   => false,
                        'company_id'   => $wc->company_id,
                        'company_slug' => $slug,
                    ];
                }
            }
        }

        // 3. Long Term Loans with Remaining Tenor <= 1 Month (or Final Payment Review)
        $ltlQuery = LongTermLoan::with(['company', 'bank'])->where('is_active', true);
        if ($companyId) {
            $ltlQuery->where('company_id', $companyId);
        }

        $ltls = $ltlQuery->whereNotNull('remaining_tenor_months')
            ->where('remaining_tenor_months', '<=', 2)
            ->get();

        foreach ($ltls as $ltl) {
            $companyName = $ltl->company->name ?? 'Sub Company';
            $bankName = $ltl->bank->name ?? 'Bank';
            $bankCode = $ltl->bank->bank_code ?? 'BNK';
            $amountRaw = (float) ($ltl->outstanding_amount ?: $ltl->facility_amount);
            $amountFormatted = number_format($amountRaw, 2);
            $slug = $ltl->company->slug ?? '';
            $remTenor = (int) $ltl->remaining_tenor_months;

            if ($remTenor <= 0 && $amountRaw > 0) {
                $alerts[] = [
                    'id'           => 'ltl-' . $ltl->id,
                    'type'         => 'danger',
                    'status_label' => 'Maturity / Overdue',
                    'category'     => 'Long Term Loan',
                    'category_key' => 'ltl',
                    'title'        => 'Long Term Loan Tenor Expired',
                    'message'      => "{$ltl->loan_type} of {$ltl->currency} {$amountFormatted} at {$bankName} has reached 0 remaining tenor. Outstanding balance requires immediate settlement or restructuring.",
                    'url'          => url("/{$slug}/long-term-loans"),
                    'company_name' => $companyName,
                    'bank_name'    => $bankName,
                    'bank_code'    => $bankCode,
                    'reference'    => $ltl->loan_type ?? ('LTL #' . $ltl->id),
                    'amount'       => $amountRaw,
                    'currency'     => $ltl->currency ?? 'LKR',
                    'date'         => $ltl->entry_date ? $ltl->entry_date->format('Y-m-d') : date('Y-m-d'),
                    'days_left'    => 0,
                    'icon'         => 'rate_revision',
                    'is_pledged'   => false,
                    'company_id'   => $ltl->company_id,
                    'company_slug' => $slug,
                ];
            } else {
                $alerts[] = [
                    'id'           => 'ltl-' . $ltl->id,
                    'type'         => $remTenor <= 1 ? 'warning' : 'info',
                    'status_label' => $remTenor <= 1 ? 'Final Months' : 'Upcoming Review',
                    'category'     => 'Long Term Loan',
                    'category_key' => 'ltl',
                    'title'        => 'Long Term Loan Maturing Soon',
                    'message'      => "{$ltl->loan_type} at {$bankName} has only {$remTenor} month(s) remaining with an outstanding balance of {$ltl->currency} {$amountFormatted}.",
                    'url'          => url("/{$slug}/long-term-loans"),
                    'company_name' => $companyName,
                    'bank_name'    => $bankName,
                    'bank_code'    => $bankCode,
                    'reference'    => $ltl->loan_type ?? ('LTL #' . $ltl->id),
                    'amount'       => $amountRaw,
                    'currency'     => $ltl->currency ?? 'LKR',
                    'date'         => $ltl->entry_date ? $ltl->entry_date->format('Y-m-d') : date('Y-m-d'),
                    'days_left'    => $remTenor * 30,
                    'icon'         => 'rate_revision',
                    'is_pledged'   => false,
                    'company_id'   => $ltl->company_id,
                    'company_slug' => $slug,
                ];
            }
        }

        // Attach Email Dispatch status to each alert from notification_dispatches table
        if (!empty($alerts)) {
            $alertIds = array_column($alerts, 'id');
            $dispatches = NotificationDispatch::whereIn('alert_id', $alertIds)
                ->where('status', 'sent')
                ->latest('sent_at')
                ->get()
                ->groupBy('alert_id');

            foreach ($alerts as &$alert) {
                $logs = $dispatches->get($alert['id']);
                if ($logs && $logs->isNotEmpty()) {
                    $latest = $logs->first();
                    $alert['email_sent']             = true;
                    $alert['email_sent_at']          = $latest->sent_at ? $latest->sent_at->format('d/m/Y H:i') : null;
                    $alert['email_recipients_count'] = $logs->count();
                    $alert['email_recipients']       = $logs->pluck('recipient_email')->unique()->values()->all();
                } else {
                    $alert['email_sent']             = false;
                    $alert['email_sent_at']          = null;
                    $alert['email_recipients_count'] = 0;
                    $alert['email_recipients']       = [];
                }
            }
            unset($alert);
        }

        // Sort: Most critical (lowest/negative days_left) first
        usort($alerts, fn($a, $b) => $a['days_left'] <=> $b['days_left']);

        return $alerts;
    }

    /**
     * Get summary counts of alerts for top-bar badge & widget cards.
     */
    public static function getSummary(?int $companyId = null, int $daysAhead = 30): array
    {
        $alerts = self::getAlerts($companyId, $daysAhead);

        $overdueAlerts = array_filter($alerts, fn($a) => $a['days_left'] < 0);
        $urgentAlerts  = array_filter($alerts, fn($a) => $a['days_left'] >= 0 && $a['days_left'] <= 7);
        $soonAlerts    = array_filter($alerts, fn($a) => $a['days_left'] > 7);

        return [
            'total_count'    => count($alerts),
            'overdue_count'  => count($overdueAlerts),
            'overdue_amount' => array_sum(array_column($overdueAlerts, 'amount')),
            'urgent_count'   => count($urgentAlerts),
            'urgent_amount'  => array_sum(array_column($urgentAlerts, 'amount')),
            'soon_count'     => count($soonAlerts),
            'soon_amount'    => array_sum(array_column($soonAlerts, 'amount')),
            'fd_count'       => count(array_filter($alerts, fn($a) => $a['category_key'] === 'fd')),
            'wc_count'       => count(array_filter($alerts, fn($a) => $a['category_key'] === 'wc')),
            'ltl_count'      => count(array_filter($alerts, fn($a) => $a['category_key'] === 'ltl')),
        ];
    }

    /**
     * Dispatch email notifications for alerts to all eligible users whose groups have email notifications enabled.
     *
     * @param int|null $companyId
     * @param string|null $targetAlertId
     * @return array
     */
    public static function dispatchAlertEmails(?int $companyId = null, ?string $targetAlertId = null): array
    {
        $alerts = self::getAlerts($companyId, 90);

        if ($targetAlertId) {
            $alerts = array_filter($alerts, fn($a) => $a['id'] === $targetAlertId);
        }

        if (empty($alerts)) {
            return [
                'success'           => true,
                'dispatched_count'  => 0,
                'recipient_count'   => 0,
                'message'           => 'No active alerts found to dispatch.',
            ];
        }

        // 1. Find all active groups with email notifications enabled
        $activeGroups = Group::with(['users' => fn($q) => $q->where('is_active', true)])
            ->where('email_notifications_enabled', true)
            ->get();

        if ($activeGroups->isEmpty()) {
            return [
                'success'          => false,
                'dispatched_count' => 0,
                'recipient_count'  => 0,
                'message'          => 'No user groups currently have Email Notifications enabled. Please enable "Send Email Notifications" in Admin -> Groups.',
            ];
        }

        $sentCount = 0;
        $uniqueRecipients = [];
        $errors = [];

        foreach ($alerts as $alert) {
            $alertCompanyId = $alert['company_id'] ?? null;
            if (!$alertCompanyId) continue;

            // Find eligible recipient users for this alert's company
            $eligibleUsers = collect();

            foreach ($activeGroups as $group) {
                $hasAccess = false;
                if ($group->isGroup()) {
                    $hasAccess = in_array((int)$alertCompanyId, $group->company_ids ?? [], true);
                } else {
                    $hasAccess = ($group->company_id == $alertCompanyId);
                }

                if ($hasAccess) {
                    $eligibleUsers = $eligibleUsers->merge($group->users);
                }
            }

            $eligibleUsers = $eligibleUsers->unique('id');

            foreach ($eligibleUsers as $user) {
                if (empty($user->email)) continue;

                $subject = "[Treasury Alert] {$alert['company_name']} - {$alert['title']} ({$alert['status_label']})";
                
                $body = "Dear {$user->name},\n\n"
                    . "This is an automated Treasury Facility Expiry / Maturity Alert for {$alert['company_name']}.\n\n"
                    . "--------------------------------------------------------\n"
                    . "ALERT DETAILS:\n"
                    . "--------------------------------------------------------\n"
                    . "• Facility / Item : {$alert['title']}\n"
                    . "• Category        : {$alert['category']}\n"
                    . "• Entity / Company: {$alert['company_name']}\n"
                    . "• Financial Inst. : {$alert['bank_name']} ({$alert['bank_code']})\n"
                    . "• Reference / No. : {$alert['reference']}\n"
                    . "• Facility Amount : {$alert['currency']} " . number_format($alert['amount'], 2) . "\n"
                    . "• Due / Maturity  : {$alert['date']}\n"
                    . "• Urgency Status  : {$alert['status_label']} (" . ($alert['days_left'] < 0 ? abs($alert['days_left']) . ' days overdue' : $alert['days_left'] . ' days remaining') . ")\n\n"
                    . "MESSAGE:\n"
                    . "{$alert['message']}\n\n"
                    . "ACTION REQUIRED:\n"
                    . "Please log in to the George Steuart Treasury Management Portal to review, settle, or renew this facility:\n"
                    . "{$alert['url']}\n\n"
                    . "--------------------------------------------------------\n"
                    . "George Steuart Treasury Management System\n"
                    . "Automated Notification Service";

                $mailResult = MailSettingService::sendTestMail($user->email, $subject, $body);

                if ($mailResult['success']) {
                    NotificationDispatch::create([
                        'alert_id'        => $alert['id'],
                        'company_id'      => $alertCompanyId,
                        'user_id'         => $user->id,
                        'recipient_email' => $user->email,
                        'subject'         => $subject,
                        'status'          => 'sent',
                        'sent_at'         => now(),
                    ]);
                    $sentCount++;
                    $uniqueRecipients[$user->email] = true;
                } else {
                    NotificationDispatch::create([
                        'alert_id'        => $alert['id'],
                        'company_id'      => $alertCompanyId,
                        'user_id'         => $user->id,
                        'recipient_email' => $user->email,
                        'subject'         => $subject,
                        'status'          => 'failed',
                        'error_message'   => $mailResult['message'] ?? 'Unknown error',
                        'sent_at'         => now(),
                    ]);
                    $errors[] = "Failed sending to {$user->email}: " . ($mailResult['message'] ?? 'SMTP Error');
                }
            }
        }

        return [
            'success'          => $sentCount > 0 || empty($errors),
            'dispatched_count' => $sentCount,
            'recipient_count'  => count($uniqueRecipients),
            'errors'           => $errors,
            'message'          => $sentCount > 0
                ? "Successfully dispatched {$sentCount} alert email(s) to " . count($uniqueRecipients) . " authorized recipient(s)."
                : (count($errors) > 0 ? "Dispatch completed with errors: " . implode(', ', array_slice($errors, 0, 2)) : "No emails needed to be sent."),
        ];
    }
}
