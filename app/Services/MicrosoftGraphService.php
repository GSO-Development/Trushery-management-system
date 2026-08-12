<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    /**
     * Get OAuth2 Access Token using Client Credentials Grant
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('azure_graph_access_token', 3000, function () {
            $tenantId     = env('AZURE_TENANT_ID');
            $clientId     = env('AZURE_CLIENT_ID');
            $clientSecret = env('AZURE_CLIENT_SECRET');

            if (! $tenantId || ! $clientId || ! $clientSecret) {
                return null;
            }

            $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
            $response = Http::withoutVerifying()->asForm()->post($tokenUrl, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]);

            if ($response->failed()) {
                Log::error('Azure AD Token error: ' . $response->body());
                return null;
            }

            return $response->json()['access_token'] ?? null;
        });
    }

    /**
     * Search tenant users by query string (displayName, mail, userPrincipalName)
     */
    public function searchUsers(string $query): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return [];
        }

        $safeQuery = str_replace("'", "''", $query);
        $filter    = "startswith(mail, '{$safeQuery}') or startswith(userPrincipalName, '{$safeQuery}') or startswith(displayName, '{$safeQuery}')";

        $url = "https://graph.microsoft.com/v1.0/users?\$filter=" . urlencode($filter) . "&\$top=10&\$select=id,displayName,mail,userPrincipalName,jobTitle,department";

        $response = Http::withoutVerifying()->withToken($token)->get($url);

        if ($response->failed()) {
            Log::error('Azure AD Graph search error: ' . $response->body());
            return [];
        }

        $items   = $response->json()['value'] ?? [];
        $results = [];

        foreach ($items as $user) {
            $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;
            if ($email) {
                $results[] = [
                    'id'                => $user['id'],
                    'name'              => $user['displayName'] ?? '',
                    'email'             => strtolower($email),
                    'userPrincipalName' => strtolower($user['userPrincipalName'] ?? $email),
                    'jobTitle'          => $user['jobTitle'] ?? null,
                    'department'        => $user['department'] ?? null,
                ];
            }
        }

        return $results;
    }

    /**
     * Search tenant departments / organization units by query string
     */
    public function searchDepartments(string $query): array
    {
        $query = trim($query);
        if (strlen($query) < 1) {
            return [];
        }

        // Standard group tenant companies & departments
        $standardDepartments = [
            ['name' => 'George Steuart Health (Pvt) Ltd', 'slug' => 'health'],
            ['name' => 'Optimize Pharma (Pvt) Ltd', 'slug' => 'optimize'],
            ['name' => 'George Steuart Travels (Pvt) Ltd', 'slug' => 'travels'],
            ['name' => 'George Steuart Solutions (Pvt) Ltd', 'slug' => 'solutions'],
            ['name' => 'George Steuart Insurance Brokers (GSIB)', 'slug' => 'gsib'],
            ['name' => 'Citrus Waskaduwa PLC', 'slug' => 'waskaduwa'],
            ['name' => 'Citrus Hikkaduwa PLC', 'slug' => 'hikkaduwa'],
            ['name' => 'Citrus Silver Ltd', 'slug' => 'citrus_silver'],
            ['name' => 'Citrus Leisure PLC', 'slug' => 'citrus_leisure'],
            ['name' => 'Citrus Vacations (LT)', 'slug' => 'citrus_lt'],
            ['name' => 'George Steuart Consumer (Pvt) Ltd', 'slug' => 'gs_consumer'],
            ['name' => 'HVA Foods PLC', 'slug' => 'hva_foods'],
            ['name' => 'George Steuart & Company Ltd', 'slug' => 'georgesteuart'],
        ];

        // Query Graph API users to collect Azure departments dynamically
        $graphDepartments = [];
        $token = $this->getAccessToken();
        if ($token) {
            $safeQuery = str_replace("'", "''", $query);
            $filter    = "startswith(department, '{$safeQuery}') or startswith(displayName, '{$safeQuery}')";
            $url       = "https://graph.microsoft.com/v1.0/users?\$filter=" . urlencode($filter) . "&\$top=20&\$select=id,displayName,department";
            $response  = Http::withoutVerifying()->withToken($token)->get($url);

            if ($response->successful()) {
                $items = $response->json()['value'] ?? [];
                foreach ($items as $item) {
                    if (! empty($item['department'])) {
                        $deptName = trim($item['department']);
                        $deptSlug = \Illuminate\Support\Str::slug($deptName);
                        $graphDepartments[$deptSlug] = [
                            'name' => \Illuminate\Support\Str::title($deptName),
                            'slug' => str_replace('-', '_', $deptSlug),
                        ];
                    }
                }
            }
        }

        // Merge standard & graph departments
        $all = [];
        foreach ($standardDepartments as $sd) {
            $all[$sd['slug']] = $sd;
        }
        foreach ($graphDepartments as $slug => $gd) {
            if (! isset($all[$slug])) {
                $all[$slug] = $gd;
            }
        }

        // Filter by $query
        $matched = [];
        $lowerQuery = strtolower($query);
        foreach ($all as $item) {
            if (str_contains(strtolower($item['name']), $lowerQuery) || str_contains(strtolower($item['slug']), $lowerQuery)) {
                $matched[] = $item;
            }
        }

        return array_values($matched);
    }
}
