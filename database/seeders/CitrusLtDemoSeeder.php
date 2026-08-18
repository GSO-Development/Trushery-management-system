<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Bank;
use App\Models\CompanyBankAccount;
use App\Models\CashPositionEntry;
use App\Models\CashMovementEntry;
use App\Models\LongTermLoan;
use App\Models\WorkingCapitalLoan;
use App\Models\FixedDeposit;
use Carbon\Carbon;

class CitrusLtDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'citrus_lt')->first();
        if (!$company) {
            echo "Citrus LT company not found.\n";
            return;
        }

        $comBank = Bank::where('bank_code', '7056')->first() ?? Bank::where('name', 'LIKE', '%Commercial%')->first();
        $hnb     = Bank::where('bank_code', '7083')->first() ?? Bank::where('name', 'LIKE', '%Hatton%')->first();
        $sampath = Bank::where('bank_code', '7278')->first() ?? Bank::where('name', 'LIKE', '%Sampath%')->first();
        $ntb     = Bank::where('bank_code', '7162')->first() ?? Bank::where('name', 'LIKE', '%Nations%')->first();
        $boc     = Bank::where('bank_code', '7010')->first() ?? Bank::where('name', 'LIKE', '%Ceylon%')->first();
        $ndb     = Bank::where('bank_code', '7214')->first() ?? Bank::where('name', 'LIKE', '%NDB%')->first();
        $seylan  = Bank::where('bank_code', '7287')->first() ?? Bank::where('name', 'LIKE', '%Seylan%')->first();

        // 1. BANK ACCOUNTS & DAILY CASH ENTRIES
        CashPositionEntry::where('company_id', $company->id)->delete();
        CompanyBankAccount::where('company_id', $company->id)->delete();

        $accountsData = [
            [
                'bank_id'        => $comBank->id ?? 3,
                'account_number' => '1000-8492-3301',
                'account_type'   => 'Current Account',
                'currency'       => 'LKR',
                'opening'        => 14500000.00,
                'inflows'        => 3250000.00,
                'outflows'       => 1800000.00,
                'restricted'     => 500000.00,
                'remarks'        => 'Main Operations & Vendor Payments',
            ],
            [
                'bank_id'        => $hnb->id ?? 4,
                'account_number' => '0040-1099-5620',
                'account_type'   => 'Operating Account',
                'currency'       => 'LKR',
                'opening'        => 8200000.00,
                'inflows'        => 1100000.00,
                'outflows'       => 4500000.00,
                'restricted'     => 0.00,
                'remarks'        => 'Payroll & Operational Overhead',
            ],
            [
                'bank_id'        => $sampath->id ?? 5,
                'account_number' => '0012-3004-9981',
                'account_type'   => 'Revenue Collection A/C',
                'currency'       => 'LKR',
                'opening'        => 22100000.00,
                'inflows'        => 6800000.00,
                'outflows'       => 2400000.00,
                'restricted'     => 2000000.00,
                'remarks'        => 'Direct Guest & Booking Inflows',
            ],
            [
                'bank_id'        => $ntb->id ?? 8,
                'account_number' => '7162-8820-0014',
                'account_type'   => 'Foreign Currency A/C (USD)',
                'currency'       => 'USD',
                'opening'        => 45000.00,
                'inflows'        => 12500.00,
                'outflows'       => 8000.00,
                'restricted'     => 0.00,
                'remarks'        => 'Online Travel Agent (OTA) Settlement',
            ],
            [
                'bank_id'        => $boc->id ?? 1,
                'account_number' => '7010-0003-8821',
                'account_type'   => 'Statutory Escrow Reserve',
                'currency'       => 'LKR',
                'opening'        => 5000000.00,
                'inflows'        => 0.00,
                'outflows'       => 0.00,
                'restricted'     => 5000000.00,
                'remarks'        => 'Lien Pledged for Government Tender',
            ],
        ];

        foreach ($accountsData as $acc) {
            $account = CompanyBankAccount::create([
                'company_id'     => $company->id,
                'bank_id'        => $acc['bank_id'],
                'account_number' => $acc['account_number'],
                'account_type'   => $acc['account_type'],
                'currency'       => $acc['currency'],
                'notes'          => $acc['remarks'],
            ]);

            $closing = $acc['opening'] + $acc['inflows'] - $acc['outflows'];

            CashPositionEntry::create([
                'company_id'              => $company->id,
                'company_bank_account_id' => $account->id,
                'bank_id'                 => $acc['bank_id'],
                'entry_date'              => Carbon::today()->toDateString(),
                'opening_balance'         => $acc['opening'],
                'cash_in'                 => $acc['inflows'],
                'cash_out'                => $acc['outflows'],
                'restricted_cash'         => $acc['restricted'],
                'closing_balance'         => $closing,
                'currency'                => $acc['currency'],
                'remarks'                 => $acc['remarks'],
                'user_id'                 => 41,
            ]);
        }

        // Cash movement summary
        CashMovementEntry::updateOrCreate(
            ['company_id' => $company->id, 'entry_date' => Carbon::today()->toDateString()],
            [
                'customer_collections' => 9150000.00,
                'loan_drawdowns'       => 2000000.00,
                'supplier_payments'    => 4200000.00,
                'salaries'             => 2500000.00,
                'taxes'                => 1100000.00,
                'loan_repayments'      => 900000.00,
                'other_payments'       => 0.00,
                'user_id'              => 41,
            ]
        );

        // 2. LONG TERM LOANS
        LongTermLoan::where('company_id', $company->id)->delete();

        LongTermLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $comBank->id ?? 3,
            'loan_type'               => 'Term Loan - Hotel Refurbishment Phase II',
            'facility_amount'         => 150000000.00,
            'tenor_months'            => 84,
            'granted_date'            => Carbon::now()->subMonths(46)->toDateString(),
            'interest_rate'           => 13.500,
            'remaining_tenor_months'  => 38,
            'outstanding_amount'      => 92450000.00,
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        LongTermLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $hnb->id ?? 4,
            'loan_type'               => 'Syndicated Solar Power Project Loan',
            'facility_amount'         => 80000000.00,
            'tenor_months'            => 60,
            'granted_date'            => Carbon::now()->subMonths(59)->toDateString(),
            'interest_rate'           => 14.250,
            'remaining_tenor_months'  => 1, // 🟠 CRITICAL (1 Month Left)
            'outstanding_amount'      => 14200000.00,
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        LongTermLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $sampath->id ?? 5,
            'loan_type'               => 'Capital Expansion & Kitchen Facility',
            'facility_amount'         => 45000000.00,
            'tenor_months'            => 36,
            'granted_date'            => Carbon::now()->subMonths(37)->toDateString(),
            'interest_rate'           => 15.000,
            'remaining_tenor_months'  => 0, // 🔴 RED / Tenor Expired!
            'outstanding_amount'      => 8500000.00,
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        LongTermLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $ndb->id ?? 6,
            'loan_type'               => 'Guest Villa Development Facility',
            'facility_amount'         => 60000000.00,
            'tenor_months'            => 48,
            'granted_date'            => Carbon::now()->subMonths(48)->toDateString(),
            'interest_rate'           => 12.750,
            'remaining_tenor_months'  => 0,
            'outstanding_amount'      => 0.00, // 🟢 SETTLED
            'action_type'             => 'settle_loan',
            'settlement_type'         => 'all',
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        // 3. WORKING CAPITAL LOANS
        WorkingCapitalLoan::where('company_id', $company->id)->delete();

        WorkingCapitalLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $comBank->id ?? 3,
            'facility_type'           => 'Import Loan (F&B Supplies)',
            'tenor'                   => '90 Days',
            'facility_amount'         => 25000000.00,
            'obtained_date'           => Carbon::now()->subDays(98)->toDateString(),
            'settlement_date'         => Carbon::now()->subDays(8)->toDateString(), // 🔴 RED / 8 Days Overdue!
            'interest_rate'           => 14.500,
            'outstanding_amount'      => 18500000.00,
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        WorkingCapitalLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $hnb->id ?? 4,
            'facility_type'           => 'Revolving Credit Line',
            'tenor'                   => '60 Days',
            'facility_amount'         => 40000000.00,
            'obtained_date'           => Carbon::now()->subDays(56)->toDateString(),
            'settlement_date'         => Carbon::now()->addDays(4)->toDateString(), // 🟠 CRITICAL / Due in 4 Days!
            'interest_rate'           => 15.200,
            'outstanding_amount'      => 28000000.00,
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        WorkingCapitalLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $sampath->id ?? 5,
            'facility_type'           => 'Short-Term Pledge Loan',
            'tenor'                   => '90 Days',
            'facility_amount'         => 15000000.00,
            'obtained_date'           => Carbon::now()->subDays(62)->toDateString(),
            'settlement_date'         => Carbon::now()->addDays(28)->toDateString(), // 🔵 Active / 28 Days Left
            'interest_rate'           => 13.800,
            'outstanding_amount'      => 12000000.00,
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        WorkingCapitalLoan::create([
            'company_id'              => $company->id,
            'bank_id'                 => $ntb->id ?? 8,
            'facility_type'           => 'Temporary Overdraft Facility',
            'tenor'                   => '30 Days',
            'facility_amount'         => 30000000.00,
            'obtained_date'           => Carbon::now()->subDays(45)->toDateString(),
            'settlement_date'         => Carbon::now()->subDays(15)->toDateString(),
            'interest_rate'           => 16.000,
            'outstanding_amount'      => 0.00, // 🟢 SETTLED
            'action_type'             => 'settle_loan',
            'settlement_type'         => 'all',
            'currency'                => 'LKR',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        // 4. FIXED DEPOSITS
        FixedDeposit::where('company_id', $company->id)->delete();

        FixedDeposit::create([
            'company_id'              => $company->id,
            'bank_id'                 => $comBank->id ?? 3,
            'fd_number'               => 'FD-CB-782190',
            'amount'                  => 35000000.00,
            'currency'                => 'LKR',
            'commencement_date'       => Carbon::now()->subMonths(12)->subDays(8)->toDateString(),
            'maturity_date'           => Carbon::now()->subDays(8)->toDateString(), // 🔴 RED / Matured 8 Days Ago!
            'tenor'                   => '12',
            'interest_rate'           => 11.500,
            'renewal_instructions'    => 'Auto Rollover with Interest Payout',
            'pledged_details'         => null,
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        FixedDeposit::create([
            'company_id'              => $company->id,
            'bank_id'                 => $hnb->id ?? 4,
            'fd_number'               => 'FD-HNB-449128',
            'amount'                  => 20000000.00,
            'currency'                => 'LKR',
            'commencement_date'       => Carbon::now()->subMonths(6)->addDays(5)->toDateString(),
            'maturity_date'           => Carbon::now()->addDays(5)->toDateString(), // 🟠 CRITICAL / Maturing in 5 Days!
            'tenor'                   => '6',
            'interest_rate'           => 12.000,
            'renewal_instructions'    => 'Credit Interest to Operating Account',
            'pledged_details'         => null,
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        FixedDeposit::create([
            'company_id'              => $company->id,
            'bank_id'                 => $sampath->id ?? 5,
            'fd_number'               => 'FD-SB-901844',
            'amount'                  => 50000000.00,
            'currency'                => 'LKR',
            'commencement_date'       => Carbon::now()->subMonths(3)->toDateString(),
            'maturity_date'           => Carbon::now()->addMonths(3)->toDateString(), // 🔵 Active (3 Months Left)
            'tenor'                   => '6',
            'interest_rate'           => 11.750,
            'renewal_instructions'    => 'Reinvest Principal & Interest',
            'pledged_details'         => 'Pledged against Overdraft Facility #OD-1200',
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        FixedDeposit::create([
            'company_id'              => $company->id,
            'bank_id'                 => $seylan->id ?? 7,
            'fd_number'               => 'FD-SEY-USD-0021',
            'amount'                  => 50000.00,
            'currency'                => 'USD',
            'commencement_date'       => Carbon::now()->subMonths(6)->toDateString(),
            'maturity_date'           => Carbon::now()->addMonths(6)->toDateString(), // 🔵 Active USD
            'tenor'                   => '12',
            'interest_rate'           => 5.250,
            'renewal_instructions'    => 'Foreign Currency Reserve Deposit',
            'pledged_details'         => null,
            'is_active'               => true,
            'user_id'                 => 41,
        ]);

        echo "Successfully seeded rich presentation data for Citrus LT (Pvt) Ltd!\n";
    }
}