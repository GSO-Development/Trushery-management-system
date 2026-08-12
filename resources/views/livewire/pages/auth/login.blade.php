<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        // Role-based redirect: admin → /admin, tenants → their portal
        $redirect = Auth::user()->getPostLoginRedirect();

        $this->redirect($redirect, navigate: false);
    }
}; ?>

<div>
    {{-- Flash error messages (e.g. from Microsoft OAuth callback) --}}
    @if (session('error'))
        <div class="mb-4 p-4 rounded-lg border border-red-200 bg-red-50 flex items-start gap-3">
            <svg class="w-5 h-5 text-[#c3122e] mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        {{-- Email Address --}}
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input
                wire:model="form.email"
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input
                wire:model="form.password"
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded border-gray-300 text-[#c3122e] shadow-sm focus:ring-[#c3122e]" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-[#c3122e] hover:text-[#9e0e24] font-medium underline-offset-4 hover:underline transition-colors"
                   href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        {{-- Login Button --}}
        <button type="submit"
            class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-lg bg-[#c3122e] hover:bg-[#9e0e24] text-white font-semibold text-sm tracking-wide shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#c3122e] focus:ring-offset-2">
            <svg wire:loading wire:target="login" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span wire:loading.remove wire:target="login">{{ __('Sign In to Portal') }}</span>
            <span wire:loading wire:target="login">{{ __('Authenticating…') }}</span>
        </button>
    </form>

    {{-- Divider --}}
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-3 text-gray-400 font-medium">or continue with</span>
        </div>
    </div>

    {{-- Microsoft Sign-In Button --}}
    <a href="{{ route('auth.microsoft') }}"
        class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-lg border border-gray-200 bg-white hover:border-[#c3122e] hover:bg-[#fdf2f4] text-gray-700 font-medium text-sm transition-all duration-200 shadow-sm group">
        {{-- Official Microsoft Logo SVG --}}
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 21 21">
            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
        </svg>
        <span class="group-hover:text-[#c3122e] transition-colors">Sign in with Microsoft</span>
    </a>
</div>
