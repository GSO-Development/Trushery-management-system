<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankEntry;
use App\Models\Company;
use App\Models\Group;
use App\Models\LongTermLoan;
use App\Models\WorkingCapitalLoan;
use App\Models\FixedDeposit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ═════════════════════════════════════════════════════════════════════
        // 1. SEED ALL BANKS (from Excel sheet — all banks actually used)
        // ═════════════════════════════════════════════════════════════════════
        $sriLankaBanks = [
            // Core banks from Excel
            ['name' => 'Bank of Ceylon',                  'short_name' => 'BOC',        'bank_code' => '7010', 'swift_code' => 'BCEYLKLX'],
            ['name' => 'People\'s Bank',                  'short_name' => 'PB',          'bank_code' => '7135', 'swift_code' => 'PSBKLKLX'],
            ['name' => 'Hatton National Bank',             'short_name' => 'HNB',         'bank_code' => '7083', 'swift_code' => 'HNBNLKLX'],
            ['name' => 'Sampath Bank',                    'short_name' => 'SAMPATH',     'bank_code' => '7278', 'swift_code' => 'BSAMLKLX'],
            ['name' => 'National Development Bank',       'short_name' => 'NDB',         'bank_code' => '7214', 'swift_code' => 'NDBLLKLX'],
            ['name' => 'Seylan Bank',                     'short_name' => 'SEYLAN',      'bank_code' => '7287', 'swift_code' => 'SEYBLKLX'],
            ['name' => 'Nations Trust Bank',              'short_name' => 'NTB',         'bank_code' => '7162', 'swift_code' => 'NTBLLKLX'],
            ['name' => 'DFCC Bank',                       'short_name' => 'DFCC',        'bank_code' => '7454', 'swift_code' => 'DFCCLKLX'],
            ['name' => 'Pan Asia Banking Corporation',    'short_name' => 'PABC',        'bank_code' => '7311', 'swift_code' => 'PABLLKLX'],
            ['name' => 'Union Bank of Colombo',           'short_name' => 'UNION',       'bank_code' => '7302', 'swift_code' => 'UBCOLKLX'],
            ['name' => 'Cargills Bank',                   'short_name' => 'CARGILLS',    'bank_code' => '7472', 'swift_code' => 'CGILLKLX'],
            ['name' => 'NDB Wealth Management',           'short_name' => 'NDB WEALTH',  'bank_code' => '7215', 'swift_code' => 'NDBLLKLX'],
            // Other Sri Lanka banks
            ['name' => 'Commercial Bank of Ceylon',      'short_name' => 'COMBANK',     'bank_code' => '7056', 'swift_code' => 'CCEYLKLX'],
            ['name' => 'HSBC Sri Lanka',                 'short_name' => 'HSBC',        'bank_code' => '7092', 'swift_code' => 'HSBCLKLX'],
            ['name' => 'Standard Chartered Bank',        'short_name' => 'SCB',         'bank_code' => '7038', 'swift_code' => 'SCBLLKLX'],
            ['name' => 'GS & C (Intercompany)',          'short_name' => 'GS&C',        'bank_code' => 'GSC01','swift_code' => null],
            ['name' => 'GS Travel (Intercompany)',       'short_name' => 'GST-IC',      'bank_code' => 'GST01','swift_code' => null],
        ];

        $bankMap = []; // short_name => Bank model
        foreach ($sriLankaBanks as $bData) {
            $bank = Bank::firstOrCreate(
                ['bank_code' => $bData['bank_code']],
                ['name' => $bData['name']]
            );
            $bankMap[$bData['short_name']] = $bank;
            // also map trimmed common abbreviations
            $bankMap[trim($bData['short_name'])] = $bank;
        }

        // ═════════════════════════════════════════════════════════════════════
        // 2. SEED ADMIN + CEO USERS
        // ═════════════════════════════════════════════════════════════════════
        User::firstOrCreate(
            ['email' => 'admin@gs.com'],
            [
                'name'          => 'Portal Administrator',
                'password'      => Hash::make('Admin@1234'),
                'is_admin'      => true,
                'auth_provider' => 'local',
            ]
        );

        $ceoUser = User::firstOrCreate(
            ['email' => 'ceo@gs.com'],
            [
                'name'          => 'Group Chief Executive',
                'password'      => Hash::make('Ceo@1234'),
                'is_ceo'        => true,
                'is_admin'      => false,
                'auth_provider' => 'local',
            ]
        );
        $ceoUser->update(['is_ceo' => true]);

        // ═════════════════════════════════════════════════════════════════════
        // 3. ALL 10 ENTITIES — from Excel: GS Group loan _ deposit portfolio
        //
        //    Slug rules: lowercase, underscores (matches view folder name)
        //    Full name:  Exact legal name from Excel sheet
        //    Banks:      Banks actually used per company in the Excel
        // ═════════════════════════════════════════════════════════════════════
        $companiesData = [
            // ── Existing GS Core Entities ──────────────────────────────────
            [
                'slug'       => 'health',
                'name'       => 'George Steuart Health (Pvt) Ltd',
                'short_name' => 'GSH',
                'email_prefix'=> 'health',
                'banks'      => ['BOC', 'SEYLAN', 'NDB', 'DFCC', 'HNB'],
                'ltl_sample' => [
                    ['bank' => 'BOC',    'loan_type' => 'Capex funding', 'tenor' => '48 Months',                        'facility_amount' => 125000000, 'granted_date' => '2024-06-01', 'interest_rate' => 9.94, 'remaining_tenor_months' => 25, 'outstanding_amount' => 67800000],
                    ['bank' => 'SEYLAN', 'loan_type' => 'Vehicle',       'tenor' => '36 Months',                        'facility_amount' => 90000000,  'granted_date' => '2024-12-01', 'interest_rate' => 10.00,'remaining_tenor_months' => 19, 'outstanding_amount' => 47500000],
                    ['bank' => 'SEYLAN', 'loan_type' => 'Capex funding', 'tenor' => '84 months - 06 months',            'facility_amount' => 200000000, 'granted_date' => '2023-12-21', 'interest_rate' => 10.00,'remaining_tenor_months' => 56, 'outstanding_amount' => 143570000],
                    ['bank' => 'SEYLAN', 'loan_type' => 'Capex funding', 'tenor' => '84 Months with 03 months Grace',   'facility_amount' => 400000000, 'granted_date' => '2024-04-09', 'interest_rate' => 10.00,'remaining_tenor_months' => 60, 'outstanding_amount' => 305380000],
                ],
                'fd_sample'  => [
                    ['bank' => 'NDB',      'amount' => 5000000,  'commencement_date' => '2026-01-01', 'maturity_date' => '2026-07-01', 'interest_rate' => 11.00, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'DFCC',     'amount' => 10000000, 'commencement_date' => '2026-02-01', 'maturity_date' => '2026-08-01', 'interest_rate' => 12.00, 'renewal_instructions' => 'Renew with interest'],
                    ['bank' => 'HNB',      'amount' => 8000000,  'commencement_date' => '2026-03-01', 'maturity_date' => '2026-09-01', 'interest_rate' => 11.50, 'renewal_instructions' => 'Liquidate and credit to current account'],
                    ['bank' => 'BOC',      'amount' => 3000000,  'commencement_date' => '2026-01-15', 'maturity_date' => '2026-04-15', 'interest_rate' => 10.50, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'NDB WEALTH','amount' => 2500000, 'commencement_date' => '2026-04-01', 'maturity_date' => '2026-10-01', 'interest_rate' => 13.00, 'renewal_instructions' => 'Pledged against OD facility'],
                ],
            ],
            [
                'slug'       => 'optimize',
                'name'       => 'George Steuart Teas (Pvt) Ltd',
                'short_name' => 'GST',
                'email_prefix'=> 'teas',
                'banks'      => ['BOC', 'HNB', 'PB', 'NDB', 'SEYLAN'],
                'ltl_sample' => [
                    ['bank' => 'BOC', 'loan_type' => 'Term Loan', 'tenor' => '10 Years', 'facility_amount' => 500000000, 'granted_date' => '2022-01-01', 'interest_rate' => 10.50, 'remaining_tenor_months' => 68, 'outstanding_amount' => 380000000],
                    ['bank' => 'HNB', 'loan_type' => 'Term Loan', 'tenor' => '5 Years',  'facility_amount' => 200000000, 'granted_date' => '2023-01-01', 'interest_rate' => 11.00, 'remaining_tenor_months' => 30, 'outstanding_amount' => 145000000],
                    ['bank' => 'PB',  'loan_type' => 'Term Loan', 'tenor' => '3 Years',  'facility_amount' => 100000000, 'granted_date' => '2024-01-01', 'interest_rate' => 10.75, 'remaining_tenor_months' => 20, 'outstanding_amount' => 68000000],
                ],
                'fd_sample'  => [
                    ['bank' => 'HNB',  'amount' => 15000000, 'commencement_date' => '2026-01-01', 'maturity_date' => '2026-10-01', 'interest_rate' => 12.00, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'HNB',  'amount' => 8000000,  'commencement_date' => '2026-03-01', 'maturity_date' => '2026-09-01', 'interest_rate' => 11.50, 'renewal_instructions' => 'Renew with interest'],
                ],
            ],
            [
                'slug'       => 'travels',
                'name'       => 'George Steuart Travels Ltd',
                'short_name' => 'GSTVL',
                'email_prefix'=> 'travels',
                'banks'      => ['NDB', 'SAMPATH', 'CARGILLS', 'UNION'],
                'ltl_sample' => [
                    ['bank' => 'NDB',   'loan_type' => 'Moratorium Loan', 'tenor' => '5 Years', 'facility_amount' => 50000000, 'granted_date' => '2021-06-01', 'interest_rate' => 9.50, 'remaining_tenor_months' => 8, 'outstanding_amount' => 8000000],
                    ['bank' => 'UNION', 'loan_type' => 'Vehicle Loan',    'tenor' => '5 Years', 'facility_amount' => 20000000, 'granted_date' => '2022-03-01', 'interest_rate' => 12.00,'remaining_tenor_months' => 12,'outstanding_amount' => 7500000],
                ],
                'fd_sample'  => [
                    ['bank' => 'NDB',      'amount' => 5000000,  'commencement_date' => '2026-01-01', 'maturity_date' => '2026-04-01', 'interest_rate' => 11.00, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'CARGILLS', 'amount' => 3000000,  'commencement_date' => '2026-02-01', 'maturity_date' => '2026-05-01', 'interest_rate' => 10.50, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'NDB WEALTH','amount' => 2000000, 'commencement_date' => '2026-03-01', 'maturity_date' => '2026-09-01', 'interest_rate' => 12.50, 'renewal_instructions' => 'Renew at maturity'],
                ],
            ],
            [
                'slug'       => 'solutions',
                'name'       => 'George Steuart Solutions (Pvt) Ltd',
                'short_name' => 'GSSOL',
                'email_prefix'=> 'solutions',
                'banks'      => ['NTB', 'NDB', 'GST-IC'],
                'ltl_sample' => [
                    ['bank' => 'GST-IC', 'loan_type' => 'Intercompany', 'tenor' => '—', 'facility_amount' => 0, 'granted_date' => null, 'interest_rate' => 0.00, 'remaining_tenor_months' => null, 'outstanding_amount' => 0],
                ],
                'fd_sample'  => [],
            ],
            // ── New Entities ───────────────────────────────────────────────
            [
                'slug'       => 'gsib',
                'name'       => 'George Steuart Insurance Brokers (Pvt) Ltd',
                'short_name' => 'GSIB',
                'email_prefix'=> 'gsib',
                'banks'      => ['NDB WEALTH', 'CARGILLS', 'SEYLAN'],
                'ltl_sample' => [],
                'fd_sample'  => [
                    ['bank' => 'NDB WEALTH', 'amount' => 5000000,  'commencement_date' => '2026-01-01', 'maturity_date' => '2026-07-01', 'interest_rate' => 13.00, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'CARGILLS',   'amount' => 3000000,  'commencement_date' => '2026-02-01', 'maturity_date' => '2026-05-01', 'interest_rate' => 10.50, 'renewal_instructions' => 'Renew at maturity'],
                    ['bank' => 'SEYLAN',     'amount' => 4000000,  'commencement_date' => '2026-03-01', 'maturity_date' => '2026-09-01', 'interest_rate' => 11.50, 'renewal_instructions' => 'Renew at maturity'],
                ],
            ],
            [
                'slug'       => 'waskaduwa',
                'name'       => 'Waskaduwa Beach Resort PLC',
                'short_name' => 'WBR',
                'email_prefix'=> 'waskaduwa',
                'banks'      => ['SAMPATH', 'PABC'],
                'ltl_sample' => [
                    ['bank' => 'SAMPATH', 'loan_type' => 'Term Loan',      'tenor' => '96 Months', 'facility_amount' => 844400000, 'granted_date' => '2025-07-25', 'interest_rate' => 0, 'remaining_tenor_months' => 87, 'outstanding_amount' => 776260000, 'notes' => 'AWPLR + 1%'],
                    ['bank' => 'SAMPATH', 'loan_type' => 'Moratorium Loan','tenor' => '40 Months', 'facility_amount' => 533000000, 'granted_date' => '2025-07-25', 'interest_rate' => 8.00,'remaining_tenor_months' => 31, 'outstanding_amount' => 324110000],
                ],
                'fd_sample'  => [],
            ],
            [
                'slug'       => 'hikkaduwa',
                'name'       => 'Hikkaduwa Beach Resort PLC',
                'short_name' => 'HBR',
                'email_prefix'=> 'hikkaduwa',
                'banks'      => ['SAMPATH', 'PABC'],
                'ltl_sample' => [
                    ['bank' => 'SAMPATH', 'loan_type' => 'Term Loan',      'tenor' => '95 Months', 'facility_amount' => 400000000, 'granted_date' => '2025-07-25', 'interest_rate' => 0, 'remaining_tenor_months' => 85, 'outstanding_amount' => 360000000, 'notes' => 'AWPLR + 1%'],
                    ['bank' => 'SAMPATH', 'loan_type' => 'Moratorium Loan','tenor' => '35 Months', 'facility_amount' => 250000000, 'granted_date' => '2025-07-25', 'interest_rate' => 8.00,'remaining_tenor_months' => 25, 'outstanding_amount' => 180000000],
                ],
                'fd_sample'  => [],
            ],
            [
                'slug'       => 'citrus_silver',
                'name'       => 'Citrus Silver Ltd',
                'short_name' => 'CSIL',
                'email_prefix'=> 'citrus.silver',
                'banks'      => ['HNB', 'SAMPATH'],
                'ltl_sample' => [
                    ['bank' => 'HNB',    'loan_type' => 'Term Loan',              'tenor' => '37 Months', 'facility_amount' => 80000000, 'granted_date' => '2024-01-01', 'interest_rate' => 11.00,'remaining_tenor_months' => 15, 'outstanding_amount' => 45000000],
                    ['bank' => 'SAMPATH','loan_type' => 'Short Term Revolving Loan','tenor' => '4 Months','facility_amount' => 50000000, 'granted_date' => '2026-03-01', 'interest_rate' => 12.50,'remaining_tenor_months' => 2,  'outstanding_amount' => 50000000],
                ],
                'fd_sample'  => [
                    ['bank' => 'SAMPATH', 'amount' => 10000000, 'commencement_date' => '2026-01-01', 'maturity_date' => '2026-07-01', 'interest_rate' => 12.00, 'renewal_instructions' => 'Renew at maturity'],
                ],
            ],
            [
                'slug'       => 'citrus_leisure',
                'name'       => 'Citrus Leisure PLC',
                'short_name' => 'CLPLC',
                'email_prefix'=> 'citrus.leisure',
                'banks'      => ['SAMPATH', 'HNB'],
                'ltl_sample' => [],
                'fd_sample'  => [
                    ['bank' => 'SAMPATH', 'amount' => 20000000, 'commencement_date' => '2026-02-01', 'maturity_date' => '2026-08-01', 'interest_rate' => 12.00, 'renewal_instructions' => 'Renew at maturity'],
                ],
            ],
            [
                'slug'       => 'citrus_lt',
                'name'       => 'Citrus LT (Pvt) Ltd',
                'short_name' => 'CLT',
                'email_prefix'=> 'citrus.lt',
                'banks'      => ['SAMPATH', 'HNB', 'NDB'],
                'ltl_sample' => [],
                'fd_sample'  => [],
            ],
        ];

        // ═════════════════════════════════════════════════════════════════════
        // 4. CREATE COMPANIES + GROUPS + USERS + SAMPLE DATA
        // ═════════════════════════════════════════════════════════════════════
        $allNavPages = [
            'summary_dashboard',
            'long_term_loans',
            'working_capital',
            'fixed_deposits',
            'audit_logs',
        ];

        foreach ($companiesData as $cData) {
            // Company
            $company = Company::firstOrCreate(
                ['slug' => $cData['slug']],
                ['name' => $cData['name']]
            );
            // Always keep name updated
            $company->update(['name' => $cData['name']]);

            // Assign banks
            $bankIdsForCompany = collect($cData['banks'])
                ->map(fn($shortName) => $bankMap[$shortName]?->id ?? null)
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $company->banks()->sync($bankIdsForCompany);

            // Attach CEO
            $ceoUser->ceoCompanies()->syncWithoutDetaching([$company->id]);

            // ── Groups (Treasury-focused, no rate_management) ──────────────
            $treasuryGroup = Group::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Treasury'],
                ['nav_permissions' => $allNavPages]
            );
            $treasuryGroup->update(['nav_permissions' => $allNavPages]);

            $dashboardGroup = Group::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Dashboard'],
                ['nav_permissions' => ['summary_dashboard']]
            );
            $dashboardGroup->update(['nav_permissions' => ['summary_dashboard']]);

            $financeGroup = Group::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Finance'],
                ['nav_permissions' => $allNavPages]
            );
            $financeGroup->update(['nav_permissions' => $allNavPages]);

            // Update old Finance/Dashboard groups
            Group::where('company_id', $company->id)
                ->where('name', 'Finance')
                ->update(['nav_permissions' => json_encode($allNavPages)]);

            Group::where('company_id', $company->id)
                ->whereJsonContains('nav_permissions', 'rate_management')
                ->get()
                ->each(function ($g) use ($allNavPages) {
                    $perms = collect($g->nav_permissions)
                        ->reject(fn($p) => $p === 'rate_management')
                        ->values()
                        ->toArray();
                    $g->update(['nav_permissions' => $perms]);
                });

            // ── Users ──────────────────────────────────────────────────────
            $prefix = $cData['email_prefix'];
            $slug   = $cData['slug'];

            $treasuryUser = User::firstOrCreate(
                ['email' => "treasury@{$prefix}.gs.com"],
                [
                    'name'          => "{$cData['name']} — Treasury Officer",
                    'password'      => Hash::make('Treasury@1234'),
                    'company_id'    => $company->id,
                    'group_id'      => $treasuryGroup->id,
                    'is_admin'      => false,
                    'auth_provider' => 'local',
                ]
            );
            $treasuryUser->update(['company_id' => $company->id, 'group_id' => $treasuryGroup->id]);

            $finUser = User::firstOrCreate(
                ['email' => "finance@{$prefix}.gs.com"],
                [
                    'name'          => "{$cData['name']} — Finance Officer",
                    'password'      => Hash::make('Finance@1234'),
                    'company_id'    => $company->id,
                    'group_id'      => $financeGroup->id,
                    'is_admin'      => false,
                    'auth_provider' => 'local',
                ]
            );
            $finUser->update(['company_id' => $company->id, 'group_id' => $financeGroup->id]);

            User::firstOrCreate(
                ['email' => "dashboard@{$prefix}.gs.com"],
                [
                    'name'          => "{$cData['name']} — Operations Manager",
                    'password'      => Hash::make('Dashboard@1234'),
                    'company_id'    => $company->id,
                    'group_id'      => $dashboardGroup->id,
                    'is_admin'      => false,
                    'auth_provider' => 'local',
                ]
            );

            // ── Seed Long Term Loans ───────────────────────────────────────
            foreach (($cData['ltl_sample'] ?? []) as $ltl) {
                $bank = $bankMap[$ltl['bank']] ?? null;
                if (! $bank) continue;

                LongTermLoan::firstOrCreate(
                    [
                        'company_id'  => $company->id,
                        'bank_id'     => $bank->id,
                        'loan_type'   => $ltl['loan_type'],
                        'entry_date'  => '2026-04-30',
                    ],
                    [
                        'user_id'                => $treasuryUser->id,
                        'tenor'                  => $ltl['tenor'],
                        'facility_amount'        => $ltl['facility_amount'],
                        'granted_date'           => $ltl['granted_date'],
                        'interest_rate'          => $ltl['interest_rate'],
                        'remaining_tenor_months' => $ltl['remaining_tenor_months'],
                        'outstanding_amount'     => $ltl['outstanding_amount'],
                        'currency'               => 'LKR',
                        'notes'                  => $ltl['notes'] ?? null,
                    ]
                );
            }

            // ── Seed Fixed Deposits ────────────────────────────────────────
            foreach (($cData['fd_sample'] ?? []) as $fd) {
                $bank = $bankMap[$fd['bank']] ?? null;
                if (! $bank) continue;

                // Auto-calculate tenor
                $tenor = null;
                if ($fd['commencement_date'] && $fd['maturity_date']) {
                    $days = \Carbon\Carbon::parse($fd['commencement_date'])->diffInDays($fd['maturity_date']);
                    $tenor = $days >= 365 ? round($days / 365, 1).' Years' : ($days >= 30 ? round($days / 30).' Months' : $days.' Days');
                }

                FixedDeposit::firstOrCreate(
                    [
                        'company_id'        => $company->id,
                        'bank_id'           => $bank->id,
                        'commencement_date' => $fd['commencement_date'],
                    ],
                    [
                        'user_id'              => $finUser->id,
                        'amount'               => $fd['amount'],
                        'currency'             => 'LKR',
                        'maturity_date'        => $fd['maturity_date'],
                        'tenor'                => $tenor,
                        'interest_rate'        => $fd['interest_rate'],
                        'renewal_instructions' => $fd['renewal_instructions'] ?? null,
                        'pledged_details'      => null,
                        'entry_date'           => '2026-04-30',
                    ]
                );
            }
        }

        // ═════════════════════════════════════════════════════════════════════
        // 5. MAKE SURE ALL OLD GROUPS ALSO GET RATE_MANAGEMENT REMOVED
        // ═════════════════════════════════════════════════════════════════════
        Group::all()->each(function ($g) use ($allNavPages) {
            if (is_array($g->nav_permissions) && in_array('rate_management', $g->nav_permissions)) {
                $cleaned = array_values(array_diff($g->nav_permissions, ['rate_management']));
                $g->update(['nav_permissions' => $cleaned]);
            }
        });

        $this->command->info('✅  Seeded all 10 GS Group entities with companies, banks, groups, users, and sample treasury data!');
        $this->command->info('');
        $this->command->table(
            ['Entity', 'Slug', 'Treasury Login', 'Password'],
            collect($companiesData)->map(fn($c) => [
                $c['name'],
                $c['slug'],
                "treasury@{$c['email_prefix']}.gs.com",
                'Treasury@1234',
            ])->toArray()
        );
    }
}
