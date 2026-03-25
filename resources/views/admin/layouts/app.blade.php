<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — IntecWorks</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

<div class="flex h-full">
    {{-- ── Sidebar ── --}}
    <aside class="hidden lg:flex lg:flex-col w-64 bg-[#0F2556] text-white min-h-screen fixed inset-y-0 left-0 z-30">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
            <div class="w-9 h-9 rounded-lg bg-[#EE7963] flex items-center justify-center font-bold text-sm">IW</div>
            <span class="text-lg font-semibold tracking-tight">IntecWorks</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            @php
                $nav = [
                    ['route' => 'admin.dashboard',      'icon' => 'home',          'label' => 'Dashboard'],
                    ['route' => 'admin.verifications',  'icon' => 'shield-check',  'label' => 'Verifications'],
                    ['route' => 'admin.users',          'icon' => 'users',         'label' => 'Users'],
                    ['route' => 'admin.jobs',           'icon' => 'briefcase',     'label' => 'Jobs'],
                    ['route' => 'admin.orders',         'icon' => 'package',       'label' => 'Orders'],
                    ['route' => 'admin.payments',       'icon' => 'credit-card',   'label' => 'Payments'],
                    ['route' => 'admin.disputes',       'icon' => 'alert-triangle','label' => 'Disputes'],
                    ['route' => 'admin.analytics',      'icon' => 'bar-chart-2',   'label' => 'Analytics'],
                    ['route' => 'admin.notifications',  'icon' => 'bell',          'label' => 'Notifications'],
                    ['route' => 'admin.settings',       'icon' => 'settings',      'label' => 'Settings'],
                    ['route' => 'admin.api-settings',    'icon' => 'key',           'label' => 'API Settings'],
                    ['route' => 'admin.activity-logs',  'icon' => 'clipboard-list','label' => 'Activity Logs'],
                ];
            @endphp

            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['route'] . '*'); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ $active ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <x-admin-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Bottom: profile + logout --}}
        <div class="px-3 py-4 border-t border-white/10 space-y-1">
            <a href="{{ route('admin.profile') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:bg-white/10 hover:text-white transition">
                <x-admin-icon name="user" class="w-5 h-5 shrink-0" />
                Profile
            </a>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-white/70 hover:bg-red-500/20 hover:text-red-300 transition">
                    <x-admin-icon name="log-out" class="w-5 h-5 shrink-0" />
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main Content ── --}}
    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">@yield('heading', 'Dashboard')</h1>
                @hasSection('subheading')
                    <p class="text-sm text-gray-500 mt-0.5">@yield('subheading')</p>
                @endif
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500">{{ Auth::guard('admin-web')->user()?->full_name ?? 'Admin' }}</span>
                <div class="w-8 h-8 rounded-full bg-[#1E3D84] text-white flex items-center justify-center text-xs font-bold">
                    {{ strtoupper(substr(Auth::guard('admin-web')->user()?->full_name ?? 'A', 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mx-6 mt-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-6 mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
