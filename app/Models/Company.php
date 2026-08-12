<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = ['name', 'slug'];

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(CompanyLoan::class);
    }

    public function banks(): BelongsToMany
    {
        return $this->belongsToMany(Bank::class, 'company_bank');
    }

    public function bankEntries(): HasMany
    {
        return $this->hasMany(BankEntry::class);
    }

    public function companyBankAccounts(): HasMany
    {
        return $this->hasMany(CompanyBankAccount::class);
    }

    /**
     * Get available nav pages from the company's Blade views folder.
     * Scans resources/views/livewire/tenant/{slug}/ for .blade.php files.
     * Returns [nav_key => nav_label] map.
     * e.g. ['summary_dashboard' => 'Summary Dashboard', 'rate_management' => 'Rate Management']
     */
    public function getAvailableNavPages(): array
    {
        $folderPath = resource_path("views/livewire/tenant/{$this->slug}");

        if (! File::isDirectory($folderPath)) {
            return [];
        }

        // Pages excluded from nav (deprecated or removed)
        $excludedPages = ['rate_management'];

        $pages = [];
        $files = File::files($folderPath);

        foreach ($files as $file) {
            $filename = $file->getFilename();
            // Only include .blade.php files
            if (! Str::endsWith($filename, '.blade.php')) {
                continue;
            }

            // nav key = filename without .blade.php extension
            $key = Str::replace('.blade.php', '', $filename);

            // Skip excluded pages
            if (in_array($key, $excludedPages)) {
                continue;
            }

            // nav label = prettified from snake_case key
            $label = Str::of($key)->replace('_', ' ')->title()->toString();

            $pages[$key] = $label;
        }

        // Sort alphabetically by key
        ksort($pages);

        return $pages;
    }

    /**
     * Scaffold the company's views folder with the 4 standard treasury pages.
     * Creates: summary_dashboard, long_term_loans, working_capital, fixed_deposits
     *
     * Source stubs are copied from the health entity folder (the template).
     * If health doesn't exist yet, a minimal stub is written.
     */
    public function scaffoldViewFolder(): void
    {
        $folderPath   = resource_path("views/livewire/tenant/{$this->slug}");
        $templatePath = resource_path('views/livewire/tenant/health');

        File::ensureDirectoryExists($folderPath);

        $standardPages = ['summary_dashboard', 'long_term_loans', 'working_capital', 'fixed_deposits'];

        foreach ($standardPages as $page) {
            $destFile   = "{$folderPath}/{$page}.blade.php";
            $sourceFile = "{$templatePath}/{$page}.blade.php";

            if (File::exists($destFile)) {
                continue; // Never overwrite existing customised pages
            }

            if (File::exists($sourceFile)) {
                File::copy($sourceFile, $destFile);
            } else {
                // Fallback minimal stub
                File::put($destFile, "@extends('layouts.portal')\n@section('header', '".Str::of($page)->replace('_',' ')->title()."')\n@section('content')\n<p class=\"text-slate-400\">No content yet.</p>\n@endsection\n");
            }
        }
    }

    /**
     * Generate a Volt Blade stub for a company page.
     */
    private function generatePageStub(string $key, string $label, string $type): string
    {
        $companyName = $this->name;
        $companySlug = $this->slug;

        if ($type === 'summary') {
            return $this->summaryDashboardStub($companyName, $companySlug, $label);
        }

        return $this->rateManagementStub($companyName, $companySlug, $label);
    }

    private function summaryDashboardStub(string $companyName, string $companySlug, string $label): string
    {
        return <<<BLADE
<?php

use App\Models\BankEntry;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public function with(): array
    {
        \$user    = auth()->user();
        \$company = \$user->company;

        \$entries = BankEntry::with('bank')
            ->where('company_id', \$company?->id)
            ->latest('entry_date')
            ->get();

        \$totalAmount    = \$entries->sum('available_amount');
        \$avgRate        = \$entries->avg('interest_rate');
        \$banksCount     = \$company?->banks()->where('is_active', true)->count() ?? 0;

        return compact('company', 'entries', 'totalAmount', 'avgRate', 'banksCount');
    }
}; ?>

<div>
    @slot('header') {$label} @endslot

    <div class="mb-6">
        <h1 class="text-xl font-bold text-[#0f172a]">{$label}</h1>
        <p class="text-sm text-slate-500 mt-0.5">{{ \$company->name ?? '{$companyName}' }} · Executive Overview</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Credit Facilities</p>
            <p class="text-2xl font-bold text-[#0f172a]">LKR {{ number_format(\$totalAmount / 1000000, 1) }}M</p>
            <p class="text-xs text-slate-400 mt-1">Across {{ \$entries->count() }} bank entries</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Avg. Interest Rate</p>
            <p class="text-2xl font-bold text-[#c3122e]">{{ \$avgRate ? number_format(\$avgRate, 2).'%' : '—' }}</p>
            <p class="text-xs text-slate-400 mt-1">Weighted average across banks</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Assigned Banks</p>
            <p class="text-2xl font-bold text-[#0f172a]">{{ \$banksCount }}</p>
            <p class="text-xs text-slate-400 mt-1">Active bank facilities</p>
        </div>
    </div>

    <!-- Bank Entries Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-50">
            <h2 class="font-bold text-[#0f172a] text-sm">Bank Rate Summary</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-[#f8fafc]">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Bank</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Interest Rate</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Available (LKR)</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse(\$entries as \$entry)
                        <tr class="hover:bg-[#fdf2f4]/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-[#0f172a]">{{ \$entry->bank->name ?? '—' }}</td>
                            <td class="px-6 py-4 font-mono text-xs font-bold text-[#c3122e]">{{ \$entry->bank->bank_code ?? '—' }}</td>
                            <td class="px-6 py-4 text-right font-bold text-[#c3122e]">{{ number_format(\$entry->interest_rate, 2) }}%</td>
                            <td class="px-6 py-4 text-right font-semibold text-[#0f172a]">{{ number_format(\$entry->available_amount, 2) }}</td>
                            <td class="px-6 py-4 text-xs text-slate-400">{{ \$entry->updated_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-400 text-sm">No bank rate entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
BLADE;
    }

    private function rateManagementStub(string $companyName, string $companySlug, string $label): string
    {
        return <<<BLADE
<?php

use App\Models\Bank;
use App\Models\BankEntry;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.portal')] class extends Component
{
    public ?int \$selectedBankId   = null;
    public string \$selectedBankName = '';
    public string \$interestRate    = '';
    public string \$availableAmount = '';
    public string \$notes           = '';
    public bool \$showEditModal     = false;

    public function with(): array
    {
        \$user          = auth()->user();
        \$company       = \$user->company;
        \$assignedBanks = \$company ? \$company->banks()->where('is_active', true)->get() : collect();
        \$entries       = BankEntry::where('company_id', \$company?->id)->get()->keyBy('bank_id');

        return compact('company', 'assignedBanks', 'entries');
    }

    public function openEdit(int \$bankId): void
    {
        \$user    = auth()->user();
        \$company = \$user->company;
        \$bank    = Bank::findOrFail(\$bankId);
        \$entry   = BankEntry::where('company_id', \$company->id)->where('bank_id', \$bankId)->first();

        \$this->selectedBankId   = \$bank->id;
        \$this->selectedBankName = \$bank->name . ' (' . (\$bank->short_name ?: \$bank->bank_code) . ')';
        \$this->interestRate     = \$entry ? (string) \$entry->interest_rate : '';
        \$this->availableAmount  = \$entry ? (string) \$entry->available_amount : '';
        \$this->notes            = \$entry ? (string) \$entry->notes : '';
        \$this->showEditModal    = true;
    }

    public function saveEntry(): void
    {
        \$this->validate([
            'selectedBankId'  => 'required|exists:banks,id',
            'interestRate'    => 'required|numeric|min:0|max:100',
            'availableAmount' => 'required|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ]);

        \$user    = auth()->user();
        \$company = \$user->company;

        BankEntry::updateOrCreate(
            ['company_id' => \$company->id, 'bank_id' => \$this->selectedBankId],
            [
                'user_id'          => \$user->id,
                'interest_rate'    => \$this->interestRate,
                'available_amount' => \$this->availableAmount,
                'notes'            => \$this->notes,
                'entry_date'       => now(),
            ]
        );

        \$this->showEditModal = false;
        session()->flash('success', 'Bank rate updated successfully.');
    }
}; ?>

<div>
    @slot('header') {$label} @endslot

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-[#0f172a]">{$label}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ \$company->name ?? '{$companyName}' }} · Bank Facility Rate Entry</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm font-medium flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
        @forelse(\$assignedBanks as \$bank)
            @php \$entry = \$entries->get(\$bank->id); @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between hover:border-[#c3122e]/30 transition-all">
                <div>
                    <span class="px-2.5 py-1 rounded-full bg-[#fdf2f4] text-[#c3122e] text-xs font-bold font-mono border border-[#f8d7da]">{{ \$bank->bank_code }}</span>
                    <h3 class="font-bold text-[#0f172a] text-lg mt-2">{{ \$bank->name }}</h3>
                    <p class="text-xs text-slate-400 font-medium">{{ \$bank->short_name }} {{ \$bank->swift_code ? '· '.\$bank->swift_code : '' }}</p>
                    <div class="space-y-3 my-4 py-3 border-y border-slate-100">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Interest Rate</span>
                            <span class="font-bold text-lg {{ \$entry ? 'text-[#c3122e]' : 'text-slate-300' }}">{{ \$entry ? \$entry->interest_rate.'%' : 'Not set' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 font-medium">Available Amount</span>
                            <span class="font-bold text-base {{ \$entry ? 'text-[#0f172a]' : 'text-slate-300' }}">{{ \$entry ? 'LKR '.number_format(\$entry->available_amount, 2) : 'Not set' }}</span>
                        </div>
                    </div>
                </div>
                <div class="pt-2 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">{{ \$entry ? 'Updated '.\$entry->updated_at->diffForHumans() : 'No entry' }}</span>
                    <button wire:click="openEdit({{ \$bank->id }})"
                        class="px-4 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-semibold transition-colors flex items-center gap-1.5 shadow-sm shadow-[#c3122e]/20">
                        {{ \$entry ? 'Update Rates' : 'Enter Rates' }}
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400">
                <p class="font-medium text-slate-600">No banks assigned to {{ \$company->name ?? '' }} yet.</p>
                <p class="text-xs mt-1">Please ask your administrator to assign banks under Company Management.</p>
            </div>
        @endforelse
    </div>

    @if(\$showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-[#0f172a] text-base">{{ \$selectedBankName }}</h3>
                    <button wire:click="\$set('showEditModal', false)" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveEntry" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Annual Interest Rate (%) <span class="text-red-500">*</span></label>
                        <input wire:model="interestRate" type="number" step="0.001" min="0" max="100" placeholder="e.g. 11.5" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono">
                        @error('interestRate') <p class="text-xs text-red-500 mt-1">{{ \$message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Available Credit Amount (LKR) <span class="text-red-500">*</span></label>
                        <input wire:model="availableAmount" type="number" step="0.01" min="0" placeholder="e.g. 50000000.00" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] font-mono">
                        @error('availableAmount') <p class="text-xs text-red-500 mt-1">{{ \$message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Notes / Remarks (Optional)</label>
                        <textarea wire:model="notes" rows="3" placeholder="Additional conditions..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e]"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="\$set('showEditModal', false)" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition-colors">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-sm font-semibold transition-colors">Save Entry</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
BLADE;
    }
}

