<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="George Steuart Portal — Secure enterprise access for authorised team members.">
    <title>{{ config('app.name', 'Enterprise Portal') }}</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { font-family: 'Inter', sans-serif; }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-12px); }
        }
        @keyframes pulse-ring {
            0%   { transform: scale(0.95); opacity: 0.6; }
            70%  { transform: scale(1.1);  opacity: 0; }
            100% { transform: scale(0.95); opacity: 0; }
        }
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50%       { background-position: 100% 50%; }
        }

        .animate-float    { animation: float 5s ease-in-out infinite; }
        .animate-fade-in  { animation: fade-in-up 0.8s ease both; }
        .animate-fade-in-delay-1 { animation: fade-in-up 0.8s ease 0.15s both; }
        .animate-fade-in-delay-2 { animation: fade-in-up 0.8s ease 0.30s both; }
        .animate-fade-in-delay-3 { animation: fade-in-up 0.8s ease 0.45s both; }

        .hero-gradient {
            background: linear-gradient(135deg, #0f172a 0%, #1a0a10 50%, #0f172a 100%);
            background-size: 200% 200%;
            animation: gradient-shift 8s ease infinite;
        }
        .crimson-glow {
            box-shadow: 0 0 60px rgba(195, 18, 46, 0.35),
                        0 20px 60px rgba(195, 18, 46, 0.2);
        }
        .cta-btn {
            background: linear-gradient(135deg, #c3122e 0%, #9e0e24 100%);
            transition: all 0.3s ease;
        }
        .cta-btn:hover {
            background: linear-gradient(135deg, #d4142e 0%, #c3122e 100%);
            box-shadow: 0 8px 30px rgba(195, 18, 46, 0.45);
            transform: translateY(-2px);
        }
        .cta-btn:active { transform: translateY(0); }

        .stat-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            transition: background 0.3s, border-color 0.3s;
        }
        .stat-card:hover {
            background: rgba(255,255,255,0.10);
            border-color: rgba(195,18,46,0.4);
        }
    </style>
</head>
<body class="antialiased overflow-x-hidden">

    <!-- ═══════════════════ HERO SECTION ═══════════════════ -->
    <main class="hero-gradient min-h-screen relative flex items-center justify-center overflow-hidden">

        <!-- Background decorative blobs -->
        <div class="absolute top-0 left-0 w-full h-full pointer-events-none select-none">
            <div class="absolute top-[-10%] left-[-5%] w-[500px] h-[500px] rounded-full bg-[#c3122e] opacity-10 blur-[100px]"></div>
            <div class="absolute bottom-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#c3122e] opacity-8 blur-[120px]"></div>
            <div class="absolute top-[40%] left-[60%] w-[300px] h-[300px] rounded-full bg-[#c3122e] opacity-5 blur-[80px]"></div>
        </div>

        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 opacity-5"
             style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px);
                    background-size: 60px 60px;">
        </div>

        <!-- Content -->
        <div class="relative z-10 max-w-5xl mx-auto px-6 text-center">

            <!-- Floating badge -->
            <div class="animate-fade-in inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 text-white/80 text-xs font-medium backdrop-blur-sm mb-8">
                <span class="w-2 h-2 rounded-full bg-[#c3122e] animate-pulse"></span>
                Authorised Access Only — Enterprise Portal
            </div>

            <!-- Main brand logo + icon -->
            <div class="animate-fade-in-delay-1 flex justify-center mb-8">
                <div class="relative">
                    <!-- Pulse ring -->
                    <div class="absolute inset-0 rounded-3xl bg-[#c3122e] opacity-30"
                         style="animation: pulse-ring 2.5s ease-out infinite;"></div>
                    <div class="relative w-24 h-24 rounded-3xl bg-gradient-to-br from-[#c3122e] to-[#7a0a1e] flex items-center justify-center crimson-glow animate-float">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Headline -->
            <h1 class="animate-fade-in-delay-1 text-5xl sm:text-6xl lg:text-7xl font-extrabold text-white tracking-tight leading-[1.05] mb-6">
                {{ config('app.name') }}
            </h1>

            <!-- Subheadline -->
            <p class="animate-fade-in-delay-2 text-lg sm:text-xl text-slate-300 font-light max-w-2xl mx-auto leading-relaxed mb-12">
                A unified enterprise management platform with
                <span class="text-white font-medium">role-based access</span> and
                <span class="text-white font-medium">Microsoft Entra ID</span> single sign-on.
                Access is restricted to registered members only.
            </p>

            <!-- CTA Button -->
            <div class="animate-fade-in-delay-2 flex flex-col sm:flex-row gap-4 justify-center items-center mb-16">
                <a href="{{ route('login') }}"
                   id="login-portal-btn"
                   class="cta-btn group inline-flex items-center gap-3 px-8 py-4 rounded-xl text-white font-semibold text-base shadow-2xl">
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    Login to Portal
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- Stats row -->
            <div class="animate-fade-in-delay-3 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-2xl mx-auto">
                <div class="stat-card rounded-2xl p-5 text-center">
                    <p class="text-3xl font-bold text-white">RBAC</p>
                    <p class="text-xs text-slate-400 mt-1 font-medium uppercase tracking-wider">Role-Based Access</p>
                </div>
                <div class="stat-card rounded-2xl p-5 text-center">
                    <p class="text-3xl font-bold text-white">SSO</p>
                    <p class="text-xs text-slate-400 mt-1 font-medium uppercase tracking-wider">Microsoft Entra ID</p>
                </div>
                <div class="stat-card rounded-2xl p-5 text-center">
                    <p class="text-3xl font-bold text-white">256-bit</p>
                    <p class="text-xs text-slate-400 mt-1 font-medium uppercase tracking-wider">Encryption</p>
                </div>
            </div>
        </div>

        <!-- Bottom wave decoration -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 80L48 74.7C96 69.3 192 58.7 288 53.3C384 48 480 48 576 53.3C672 58.7 768 69.3 864 72C960 74.7 1056 69.3 1152 61.3C1248 53.3 1344 42.7 1392 37.3L1440 32V80H1392C1344 80 1248 80 1152 80C1056 80 960 80 864 80C768 80 672 80 576 80C480 80 384 80 288 80C192 80 96 80 48 80H0Z"
                      fill="rgba(255,255,255,0.03)"/>
            </svg>
        </div>
    </main>

    <!-- ═══════════════════ FOOTER ═══════════════════ -->
    <footer class="bg-[#0f172a] text-center py-6 text-slate-500 text-xs border-t border-white/5">
        © {{ date('Y') }} {{ config('app.name') }}. All rights reserved. &nbsp;·&nbsp;
        <span class="text-slate-600">Restricted Access System</span>
    </footer>

</body>
</html>
