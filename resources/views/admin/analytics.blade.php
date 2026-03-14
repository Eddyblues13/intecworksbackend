@extends('admin.layouts.app')

@section('heading', 'Analytics')
@section('subheading', 'Platform performance insights.')

@section('content')
{{-- Period Selector --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6 flex flex-wrap items-center gap-2">
    @foreach (['7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days', '1y' => '1 Year'] as $key => $label)
        <a href="{{ route('admin.analytics', ['period' => $key]) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium
                  {{ ($period ?? '30d') === $key ? 'bg-[#1E3D84] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} transition">
            {{ $label }}
        </a>
    @endforeach
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <x-admin-icon name="users" class="w-4.5 h-4.5" />
            </div>
            <span class="text-sm text-gray-500">New Users</span>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['new_users']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                <x-admin-icon name="dollar-sign" class="w-4.5 h-4.5" />
            </div>
            <span class="text-sm text-gray-500">Revenue</span>
        </div>
        <p class="text-2xl font-bold text-gray-900">₦{{ number_format($summary['total_revenue'], 2) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                <x-admin-icon name="briefcase" class="w-4.5 h-4.5" />
            </div>
            <span class="text-sm text-gray-500">Jobs Posted</span>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['jobs_posted']) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                <x-admin-icon name="package" class="w-4.5 h-4.5" />
            </div>
            <span class="text-sm text-gray-500">Orders</span>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_orders']) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- User Growth --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">User Growth</h3>
        @if ($userGrowth->count() > 0)
            <div class="space-y-2">
                @foreach ($userGrowth as $point)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-24 text-gray-500 text-xs">{{ $point->label }}</span>
                        <div class="flex-1 h-6 bg-gray-50 rounded-md overflow-hidden">
                            @php $max = $userGrowth->max('value') ?: 1; @endphp
                            <div class="h-full bg-blue-500 rounded-md flex items-center justify-end px-2"
                                 style="width: {{ ($point->value / $max) * 100 }}%; min-width: 30px">
                                <span class="text-xs text-white font-medium">{{ (int)$point->value }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 py-8 text-center">No data for this period.</p>
        @endif
    </div>

    {{-- Revenue --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Revenue</h3>
        @if ($revenue->count() > 0)
            <div class="space-y-2">
                @foreach ($revenue as $point)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-24 text-gray-500 text-xs">{{ $point->label }}</span>
                        <div class="flex-1 h-6 bg-gray-50 rounded-md overflow-hidden">
                            @php $maxRev = $revenue->max('value') ?: 1; @endphp
                            <div class="h-full bg-green-500 rounded-md flex items-center justify-end px-2"
                                 style="width: {{ ($point->value / $maxRev) * 100 }}%; min-width: 30px">
                                <span class="text-xs text-white font-medium">₦{{ number_format($point->value) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 py-8 text-center">No data for this period.</p>
        @endif
    </div>

    {{-- Jobs by Category --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 lg:col-span-2">
        <h3 class="font-semibold text-gray-900 mb-4">Jobs by Category</h3>
        @if ($jobsByCategory->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach ($jobsByCategory as $cat)
                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500">{{ $cat->category?->name ?? 'Unknown' }}</p>
                        <p class="text-xl font-bold text-gray-900 mt-1">{{ (int)$cat->cnt }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 py-8 text-center">No data for this period.</p>
        @endif
    </div>
</div>
@endsection
