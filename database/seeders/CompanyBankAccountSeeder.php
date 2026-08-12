<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Company;
use App\Models\CompanyBankAccount;
use Illuminate\Database\Seeder;

/**
 * CompanyBankAccountSeeder
 * ─────────────────────────────────────────────────────────────────────────────
 * Seeds all bank account numbers from the "Daily Group Cash Position Report"
 * (Sheet 1. Summary) in the Group Treasury Excel file.
 *
 * Accounts are mapped per company + bank name (fuzzy match).
 */
class CompanyBankAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Fuzzy bank name mapping → bank short_name in our DB
        $bankMap = [
            'NDB'         => 'NDB',    'NDB Wealth'  => 'NDB WEALTH', 'NDB WEALTH' => 'NDB WEALTH',
            'BOC'         => 'BOC',    'DFCC'        => 'DFCC',       'HNB'        => 'HNB',
            'Sampath'     => 'SAMPATH','Seylan'      => 'SEYLAN',     'Union'      => 'UNION',
            'Union Bank'  => 'UNION',  'NTB'         => 'NTB',        'PABC'       => 'PABC',
            'Peoples'     => 'PB',     'Comm Bank'   => 'COMBANK',    'Commercial' => 'COMBANK',
            'Commercial Bank' => 'COMBANK', 'Cargills' => 'CARGILLS', 'Pan Asia'  => 'PABC',
            'Peoples - RI'=> 'PB',     'GS&C'        => 'GS&C',
        ];

        // Company slug → array of [bankAlias, accountType, accountNumber, currency]
        $accounts = [
            // ── George Steuart Health (Pvt) Ltd
            'health' => [
                ['NDB',         'Current',          '1010 0002 5071',     'LKR'],
                ['NDB',         'Saving',           '1065-9000-6001',     'USD'],
                ['DFCC',        'Current',          '102072013996',       'USD'],
                ['DFCC',        'Current',          '101014573128',       'LKR'],
                ['Seylan',      'Current',          '0080-13334985-001',  'LKR'],
                ['Seylan',      'Current',          '0860-13334985002',   'LKR'],
                ['Sampath',     'Current',          '0091-1000-7245',     'LKR'],
                ['Sampath',     'Current',          '0009-3000-0027',     'LKR'],
                ['Sampath',     'Current',          '0018 1000 4351',     'LKR'],
                ['HNB',         'Current',          '61010277822',        'LKR'],
                ['HNB',         'Current',          '0020 1000 3748',     'LKR'],
                ['Peoples',     'Current',          '4100191976807',      'LKR'],
                ['BOC',         'Current',          '70848912',           'LKR'],
                ['Comm Bank',   'Current',          '2020045110',         'LKR'],
                ['Comm Bank',   'MMS',              '1020012905',         'LKR'],
            ],
            // ── George Steuart Teas (Pvt) Ltd
            'optimize' => [
                ['BOC',     'C/A',  '7230989',          'LKR'],
                ['BOC',     'BFCA', '7231039',          'USD'],
                ['BOC',     'BLA',  '88359063',         'USD'],
                ['BOC',     'BFCA', '71799300',         'EUR'],
                ['Peoples', 'C/A',  '148100130046167',  'LKR'],
                ['Peoples', 'BFCA', '148402130046167',  'USD'],
                ['Seylan',  'C/A',  '86013585844001',   'LKR'],
                ['Seylan',  'BFCA', '86013585844050',   'USD'],
                ['Seylan',  'BLA',  '86013585844001',   'USD'],
                ['HNB',     'C/A',  '2010015083',       'LKR'],
                ['HNB',     'C/A',  '2010016836',       'LKR'],
                ['HNB',     'BFCA', '2910138541',       'USD'],
                ['HNB',     'BLA',  '2910469986',       'USD'],
                ['NDB',     'C/A',  '101000017672',     'LKR'],
                ['NDB',     'BFCA', '101570000145',     'USD'],
                ['NDB',     'BLA',  '106840000748',     'USD'],
            ],
            // ── George Steuart Travels Ltd
            'travels' => [
                ['BOC',     'C/A', '71068290',           'LKR'],
                ['NDB',     'C/A', '101000234638',       'LKR'],
                ['Sampath', 'C/A', '000930000574',       'LKR'],
                ['Sampath', 'C/A', '000911000523',       'LKR'],
                ['Union',   'C/A', '9970101000004250',   'LKR'],
                ['Peoples', 'C/A', '148100110050706',    'LKR'],
                ['NTB',     'C/A', '100020013810',       'LKR'],
                ['Comm Bank','C/A', '1020012896',        'LKR'],
                ['NDB',     'S/A', '106630000249',       'USD'],
                ['Sampath', 'S/A', '500930004122',       'USD'],
            ],
            // ── George Steuart Solutions (Pvt) Ltd
            'solutions' => [
                ['Commercial Bank', 'Current', '1020023631',       'LKR'],
                ['Commercial Bank', 'Current', '1000161074',       'LKR'],
                ['Commercial Bank', 'Current', '1000961599',       'LKR'],
                ['NDB',             'Current', '101001033396',     'LKR'],
                ['NDB',             'Saving',  '115510157755',     'LKR'],
                ['NDB',             'Saving',  '106590006311',     'USD'],
                ['NTB',             'Current', '100020003275',     'LKR'],
                ['NTB',             'Saving',  '200020042138',     'LKR'],
                ['NTB',             'Saving',  '200020036250',     'LKR'],
                ['BOC',             'Current', '88234820',         'LKR'],
                ['BOC',             'Current', '88234806',         'LKR'],
                ['Union Bank',      'Current', '120101000003065',  'LKR'],
                ['Seylan',          'Current', '0860-13457848-001','LKR'],
                ['Pan Asia',        'Current', '100111000525',     'LKR'],
            ],
            // ── Waskaduwa Beach Resort PLC
            'waskaduwa' => [
                ['Sampath',   'Current', '002930022406',   'LKR'],
                ['Sampath',   'Current', '002930025324',   'LKR'],
                ['Sampath',   'Current', '502909000268',   'USD'],
                ['Commercial','Current', '1030018169',     'LKR'],
                ['Commercial','Current', '1000911976',     'LKR'],
                ['PABC',      'Current', '100111001078',   'LKR'],
                ['Peoples',   'Current', '309100180008189','LKR'],
                ['Seylan',    'Current', '086013589957001','LKR'],
            ],
            // ── Hikkaduwa Beach Resort PLC
            'hikkaduwa' => [
                ['Sampath',  'Current',         '2930021019',    'LKR'],
                ['Sampath',  'Current',         '2930021205',    'LKR'],
                ['Sampath',  'Current',         '11810000270',   'LKR'],
                ['Sampath',  'Current',         '502909000241',  'GBP'],
                ['Sampath',  'Current',         '502930001451',  'USD'],
                ['Sampath',  'Current',         '502930097884',  'EUR'],
                ['Commercial','Current',        '1140033233',    'LKR'],
                ['Commercial','Current',        '1000914779',    'LKR'],
                ['Peoples',  'Current',         '136100140032845','LKR'],
                ['NDB Wealth','Money Market A/C','1023274-01',   'LKR'],
                ['PABC',     'Current',         '100166000978',  'LKR'],
                ['PABC',     'Current',         '100111000880',  'LKR'],
                ['NTB',      'Current',         '100870009540',  'LKR'],
                ['Seylan',   'Current',         '86013589944001','LKR'],
            ],
            // ── Citrus Silver Ltd
            'citrus_silver' => [
                ['Sampath',   'Current', '002930027831',  'LKR'],
                ['Sampath',   'Current', '002930027858',  'LKR'],
                ['Commercial','Current', '1000923818',    'LKR'],
                ['HNB',       'Current', '081010011965',  'LKR'],
                ['HNB',       'Current', '202010003330',  'LKR'],
                ['HNB',       'Current', '081010015734',  'LKR'],
                ['HNB',       'Current', '202010003349',  'USD'],
                ['PABC',      'Current', '100311011246',  'LKR'],
            ],
            // ── Citrus Leisure PLC
            'citrus_leisure' => [
                ['NDB Wealth','Money Market A/C', '1048445-01',         'LKR'],
                ['PABC',     'Current',           '1001 1100 2815',     'LKR'],
                ['PABC',     'Current',           '1001 1100 0088',     'LKR'],
                ['Sampath',  'Current',           '0012 1000 7225',     'LKR'],
                ['Seylan',   'Current',           '0860 1358 9928 001', 'LKR'],
                ['Commercial','Current',          '1000 8947 43',       'LKR'],
                ['Peoples',  'Current',           '148 1001 0005 0085', 'LKR'],
                ['Peoples',  'Current',           '1361 001 034 18931', 'LKR'],
            ],
            // ── Citrus LT (Pvt) Ltd
            'citrus_lt' => [
                ['Commercial','Current', '1000925102',   'LKR'],
                ['HNB',       'Current', '081010014515', 'LKR'],
            ],
        ];

        // Build bank short_name → Bank model lookup
        $banks = Bank::all()->keyBy('short_name');

        foreach ($accounts as $slug => $rows) {
            $company = Company::where('slug', $slug)->first();
            if (! $company) {
                $this->command->warn("Company not found: $slug");
                continue;
            }

            foreach ($rows as [$bankAlias, $accountType, $accountNumber, $currency]) {
                $shortName = $bankMap[$bankAlias] ?? null;
                $bank = $shortName ? $banks->get($shortName) : null;

                if (! $bank) {
                    // Try case-insensitive search
                    $bank = Bank::where('short_name', 'like', "%$bankAlias%")
                                ->orWhere('name', 'like', "%$bankAlias%")
                                ->first();
                }

                if (! $bank) {
                    $this->command->warn("  Bank not found for '$bankAlias' (Company: $slug) — skipping");
                    continue;
                }

                CompanyBankAccount::firstOrCreate(
                    [
                        'company_id'    => $company->id,
                        'bank_id'       => $bank->id,
                        'account_number'=> trim($accountNumber),
                    ],
                    [
                        'account_type' => $accountType,
                        'currency'     => $currency,
                    ]
                );
            }

            $count = CompanyBankAccount::where('company_id', $company->id)->count();
            $this->command->info("  $slug → $count bank accounts");
        }

        $this->command->info('✅ Company bank accounts seeded!');
    }
}
