<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Sign In — {{ config('app.name', 'Portal') }}</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts & Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="antialiased bg-[#f8fafc] min-h-screen">

        <!-- Branded Background Pattern -->
        <div class="fixed inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-[#c3122e] opacity-5 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 rounded-full bg-[#c3122e] opacity-5 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-slate-100 opacity-40 blur-3xl"></div>
        </div>

        <div class="min-h-screen flex flex-col lg:flex-row">

            <!-- Left: Brand Panel -->
            <div class="hidden lg:flex lg:w-2/5 bg-gradient-to-br from-[#0f172a] via-[#1e1e2e] to-[#0f172a] flex-col items-center justify-center p-12 relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-[#c3122e] opacity-10 -translate-y-20 translate-x-20"></div>
                <div class="absolute bottom-0 left-0 w-80 h-80 rounded-full bg-[#c3122e] opacity-5 translate-y-20 -translate-x-20"></div>
                <div class="absolute bottom-1/3 right-0 w-48 h-48 rounded-full bg-white opacity-5 translate-x-10"></div>

                <!-- Brand content -->
                <div class="relative z-10 text-center">
                    <!-- Logo mark -->
                    <div class="w-20 h-20 rounded-2xl bg-[#c3122e] flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-[#c3122e]/30">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>

                    <h1 class="text-3xl font-bold text-white tracking-tight mb-3">
                        {{ config('app.name', 'Enterprise Portal') }}
                    </h1>
                    <p class="text-slate-400 text-base leading-relaxed max-w-xs mx-auto">
                        Secure, role-based access to your enterprise management systems.
                    </p>

                    <div class="mt-12 space-y-4">
                        <div class="flex items-center gap-3 text-slate-300 text-sm">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-[#c3122e]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Enterprise-grade security & access control</span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-300 text-sm">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-[#c3122e]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                            </div>
                            <span>Multi-tenant role-based management</span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-300 text-sm">
                            <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-[#c3122e]" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Microsoft Entra ID SSO integration</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Login Form Panel -->
            <div class="flex-1 flex flex-col items-center justify-center p-6 sm:p-10">

                <!-- Mobile Logo -->
                <div class="lg:hidden mb-8 text-center">
                    <div class="w-14 h-14 rounded-xl bg-[#c3122e] flex items-center justify-center mx-auto mb-3 shadow-lg shadow-[#c3122e]/20">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-[#0f172a]">{{ config('app.name') }}</p>
                </div>

                <!-- Login Card -->
                <div class="w-full max-w-md">
                    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 border border-slate-100 p-8">
                        <div class="mb-7">
                            <h2 class="text-2xl font-bold text-[#0f172a] tracking-tight">Welcome back</h2>
                            <p class="mt-1.5 text-sm text-slate-500">Sign in to access your portal</p>
                        </div>

                        {{ $slot }}
                    </div>

                    <p class="text-center text-xs text-slate-400 mt-6">
                        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>

    </body>
</html>
