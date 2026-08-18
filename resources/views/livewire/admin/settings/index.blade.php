<?php

use App\Models\Setting;
use App\Services\MailSettingService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.admin')] class extends Component
{
    // SMTP Configuration properties
    public string $mail_mailer = 'smtp';
    public string $mail_host = 'smtp.gmail.com';
    public int $mail_port = 587;
    public string $mail_encryption = 'tls';
    public string $mail_username = 'georgesteuartit@gmail.com';
    public string $mail_password = '';
    public string $mail_from_address = 'georgesteuartit@gmail.com';
    public string $mail_from_name = 'George Steuart Treasury';

    // Test Email Modal properties
    public bool $showTestModal = false;
    public string $test_recipient = '';
    public string $test_subject = 'George Steuart Treasury System - Test Email';
    public string $test_message = 'This is a test email sent from the George Steuart Treasury Management System to verify that SMTP configuration is functioning properly.';
    public ?string $testResultStatus = null; // 'success' or 'error'
    public ?string $testResultMessage = null;
    public bool $isSendingTest = false;

    public function mount(): void
    {
        $settings = Setting::getMailSettings();
        $this->mail_mailer       = $settings['mail_mailer'] ?? 'smtp';
        $this->mail_host         = $settings['mail_host'] ?? 'smtp.gmail.com';
        $this->mail_port         = (int) ($settings['mail_port'] ?? 587);
        $this->mail_encryption   = $settings['mail_encryption'] ?? 'tls';
        $this->mail_username     = $settings['mail_username'] ?? 'georgesteuartit@gmail.com';
        $this->mail_password     = $settings['mail_password'] ?? '';
        $this->mail_from_address = $settings['mail_from_address'] ?? 'georgesteuartit@gmail.com';
        $this->mail_from_name    = $settings['mail_from_name'] ?? 'George Steuart Treasury';

        $this->test_recipient = auth()->user()->email ?? 'admin@gs.com';
    }

    public function saveSettings(): void
    {
        $this->validate([
            'mail_host'         => 'required|string|max:255',
            'mail_port'         => 'required|integer|min:1|max:65535',
            'mail_encryption'   => 'required|in:tls,ssl,none',
            'mail_username'     => 'required|string|max:255',
            'mail_password'     => 'required|string',
            'mail_from_address' => 'required|email|max:255',
            'mail_from_name'    => 'required|string|max:255',
        ]);

        Setting::set('mail_mailer', 'smtp', false, 'mail');
        Setting::set('mail_host', $this->mail_host, false, 'mail');
        Setting::set('mail_port', (string) $this->mail_port, false, 'mail');
        Setting::set('mail_encryption', $this->mail_encryption, false, 'mail');
        Setting::set('mail_username', $this->mail_username, false, 'mail');
        Setting::set('mail_password', $this->mail_password, true, 'mail'); // Encrypted in DB
        Setting::set('mail_from_address', $this->mail_from_address, false, 'mail');
        Setting::set('mail_from_name', $this->mail_from_name, false, 'mail');

        // Apply immediately to current runtime
        MailSettingService::applyConfig();

        session()->flash('status', 'Email settings saved and applied successfully!');
    }

    public function openTestModal(): void
    {
        $this->testResultStatus = null;
        $this->testResultMessage = null;
        $this->showTestModal = true;
    }

    public function closeTestModal(): void
    {
        $this->showTestModal = false;
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'test_recipient' => 'required|email',
            'test_subject'   => 'required|string|max:255',
            'test_message'   => 'required|string',
        ]);

        $this->isSendingTest = true;
        $this->testResultStatus = null;
        $this->testResultMessage = null;

        $overrideSettings = [
            'mail_host'         => $this->mail_host,
            'mail_port'         => (int) $this->mail_port,
            'mail_encryption'   => $this->mail_encryption,
            'mail_username'     => $this->mail_username,
            'mail_password'     => $this->mail_password,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name'    => $this->mail_from_name,
        ];

        $result = MailSettingService::sendTestMail(
            $this->test_recipient,
            $this->test_subject,
            $this->test_message,
            $overrideSettings
        );

        $this->isSendingTest = false;
        if ($result['success']) {
            $this->testResultStatus = 'success';
            $this->testResultMessage = $result['message'];
        } else {
            $this->testResultStatus = 'error';
            $this->testResultMessage = $result['message'];
        }
    }
}; ?>

<div>
    @slot('header') System Settings @endslot

    {{-- Main Container --}}
    <div class="max-w-4xl mx-auto space-y-6" x-data="{ showPassword: false }">

        {{-- Page Title Banner --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#0f172a] tracking-tight flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-[#c3122e]/10 text-[#c3122e] flex items-center justify-center text-sm font-black">⚙️</span>
                    <span>System Settings &amp; Email Configuration</span>
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Manage global SMTP credentials, outgoing mail sender details &amp; test email delivery.
                </p>
            </div>

            <button type="button" wire:click="openTestModal"
                class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all shadow-md flex items-center gap-2 cursor-pointer self-start sm:self-auto">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>📧 Send Test Email</span>
            </button>
        </div>

        {{-- Flash Notification --}}
        @if (session('status'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        {{-- SMTP Email Configuration Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-[#0f172a] text-sm flex items-center gap-2">
                        <span>SMTP Email Server Configuration</span>
                        <span class="px-2 py-0.5 rounded-full bg-[#fdf2f4] text-[#c3122e] text-[10px] font-bold border border-[#f8d7da]">Active Mailer</span>
                    </h2>
                    <p class="text-xs text-slate-400">Settings are stored securely in the database and encrypted.</p>
                </div>
            </div>

            <form wire:submit="saveSettings" class="p-6 space-y-5">
                {{-- Host & Port --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            SMTP Host <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="mail_host" required placeholder="smtp.gmail.com"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                        @error('mail_host') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            SMTP Port <span class="text-red-500">*</span>
                        </label>
                        <input type="number" wire:model="mail_port" required placeholder="587"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                        @error('mail_port') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Encryption & Username --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Encryption Protocol <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="mail_encryption" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                            <option value="tls">TLS (Port 587 - Recommended)</option>
                            <option value="ssl">SSL (Port 465)</option>
                            <option value="none">None (Insecure / Local)</option>
                        </select>
                        @error('mail_encryption') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            SMTP Username / Email <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="mail_username" required placeholder="georgesteuartit@gmail.com"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                        @error('mail_username') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Password with Toggle --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        SMTP Password / Google App Password <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" wire:model="mail_password" required placeholder="Password or 16-character App Password"
                            class="w-full px-3.5 py-2.5 pr-24 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-600 transition-colors">
                            <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                    @error('mail_password') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    <p class="text-[11px] text-slate-400 mt-1">
                        🔒 Encrypted securely in the database with AES-256 before storage.
                    </p>
                </div>

                {{-- From Address & From Name --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Default From Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" wire:model="mail_from_address" required placeholder="georgesteuartit@gmail.com"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                        @error('mail_from_address') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Default From Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="mail_from_name" required placeholder="George Steuart Treasury"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none transition-all">
                        @error('mail_from_name') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Information Box on Gmail App Passwords --}}
                <div class="p-4 rounded-xl bg-blue-50/70 border border-blue-200 text-blue-900 text-xs leading-relaxed">
                    <div class="flex items-start gap-2.5">
                        <span class="text-base flex-shrink-0 mt-0.5">ℹ️</span>
                        <div>
                            <p class="font-bold mb-0.5">Note for Google / Gmail Accounts:</p>
                            <p class="text-blue-800">
                                Google requires an <strong>App Password</strong> for SMTP access. If sending fails with authentication error, generate a 16-character App Password at:
                                <a href="https://myaccount.google.com/apppasswords" target="_blank" class="underline font-bold text-blue-950">Google Account → Security → 2-Step Verification → App Passwords</a>.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <button type="button" wire:click="openTestModal"
                        class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                        Test This Configuration
                    </button>

                    <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-all shadow-md shadow-[#c3122e]/20 flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Save Configuration</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Test Email Popup Modal --}}
    @if($showTestModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
             x-data
             @keydown.escape.window="$wire.closeTestModal()">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden my-6 animate-in fade-in zoom-in-95 duration-150">
                
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-slate-100 bg-[#f8fafc] flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-[#0f172a] text-base flex items-center gap-2">
                            <span>📧 Send Test Email</span>
                        </h3>
                        <p class="text-xs text-slate-400">Verifies live connectivity to <span class="font-mono text-slate-600 font-bold">{{ $mail_host }}:{{ $mail_port }}</span></p>
                    </div>
                    <button type="button" wire:click="closeTestModal" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-4">

                    {{-- Feedback Alert --}}
                    @if($testResultStatus === 'success')
                        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-start gap-2.5">
                            <span class="text-emerald-600 text-base flex-shrink-0 mt-0.5">✅</span>
                            <div>
                                <p class="font-black">Success!</p>
                                <p class="font-normal mt-0.5">{{ $testResultMessage }}</p>
                            </div>
                        </div>
                    @elseif($testResultStatus === 'error')
                        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs leading-relaxed">
                            <div class="flex items-start gap-2.5">
                                <span class="text-red-600 text-base flex-shrink-0 mt-0.5">❌</span>
                                <div>
                                    <p class="font-black text-red-900">Failed to send test email:</p>
                                    <p class="font-mono text-[11px] mt-1 break-all bg-white/60 p-2 rounded border border-red-200">{{ $testResultMessage }}</p>
                                    <p class="text-[11px] text-red-700 mt-2">
                                        Tip: For Gmail, ensure you are using a 16-character <strong>Google App Password</strong> instead of your account login password.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Recipient Field --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Send To (Recipient Email) <span class="text-red-500">*</span>
                        </label>
                        <input type="email" wire:model="test_recipient" required placeholder="recipient@example.com"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        @error('test_recipient') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Subject Field --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Email Subject / Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="test_subject" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none">
                        @error('test_subject') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>

                    {{-- Message Body Field --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Email Body Text <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model="test_message" rows="3" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-[#c3122e]/20 focus:border-[#c3122e] outline-none"></textarea>
                        @error('test_message') <span class="text-[11px] text-red-500 font-medium">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-slate-100 bg-[#f8fafc] flex items-center justify-end gap-3">
                    <button type="button" wire:click="closeTestModal"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                        Close
                    </button>

                    <button type="button" wire:click="sendTestEmail" wire:loading.attr="disabled"
                        class="px-6 py-2 rounded-xl bg-[#c3122e] hover:bg-[#9e0e24] text-white text-xs font-bold transition-all shadow-md shadow-[#c3122e]/20 flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <span wire:loading.remove wire:target="sendTestEmail">Send Test Email Now</span>
                        <span wire:loading wire:target="sendTestEmail" class="flex items-center gap-2">
                            <svg class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Connecting &amp; Sending...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
