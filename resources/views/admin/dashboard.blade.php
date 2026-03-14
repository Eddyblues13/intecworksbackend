@extends('admin.layouts.app')

@section('heading', 'Dashboard')
@section('subheading', 'Welcome back! Here\'s an overview of the platform.')

@section('content')
{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @php
        $statCards = [
            ['label' => 'Total Users',      'value' => number_format($stats['totalUsers']),      'icon' => 'users',       'color' => 'bg-blue-50 text-blue-600'],
            ['label' => 'Active Jobs',       'value' => number_format($stats['activeJobs']),      'icon' => 'briefcase',   'color' => 'bg-amber-50 text-amber-600'],
            ['label' => 'Completed Jobs',    'value' => number_format($stats['completedJobs']),   'icon' => 'check',       'color' => 'bg-green-50 text-green-600'],
            ['label' => 'Platform Revenue',  'value' => '₦' . number_format($stats['platformRevenue'], 2), 'icon' => 'dollar-sign','color' => 'bg-purple-50 text-purple-600'],
        ];
    @endphp

    @foreach ($statCards as $card)
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">{{ $card['label'] }}</span>
                <div class="w-9 h-9 rounded-lg {{ $card['color'] }} flex items-center justify-center">
                    <x-admin-icon :name="$card['icon']" class="w-4.5 h-4.5" />
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- User breakdown --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
            <x-admin-icon name="user" class="w-5 h-5" />
        </div>
        <div>
            <p class="text-sm text-gray-500">Clients</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['totalClients']) }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
            <x-admin-icon name="briefcase" class="w-5 h-5" />
        </div>
        <div>
            <p class="text-sm text-gray-500">Artisans</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['totalArtisans']) }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <x-admin-icon name="package" class="w-5 h-5" />
        </div>
        <div>
            <p class="text-sm text-gray-500">Suppliers</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['totalSuppliers']) }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Pending Verifications --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Pending Verifications</h2>
            <a href="{{ route('admin.verifications') }}" class="text-sm text-[#1E3D84] hover:underline font-medium">View All →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse ($pendingVerifications as $user)
                <div class="px-5 py-3.5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr($user->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $user->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($user->role) }} · {{ $user->email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.verifications.show', $user->id) }}"
                       class="text-xs font-medium text-[#1E3D84] hover:underline">Review</a>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-400">No pending verifications.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent Users --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Recent Users</h2>
            <a href="{{ route('admin.users') }}" class="text-sm text-[#1E3D84] hover:underline font-medium">View All →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse ($recentUsers as $user)
                <div class="px-5 py-3.5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr($user->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $user->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ ucfirst($user->role) }} · {{ $user->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $user->account_status === 'active' ? 'bg-green-50 text-green-700' :
                           ($user->account_status === 'suspended' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                        {{ ucfirst(str_replace('_', ' ', $user->account_status)) }}
                    </span>
                </div>
            @empty
                <p class="px-5 py-8 text-center text-sm text-gray-400">No users yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Jobs --}}
<div class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">Recent Jobs</h2>
        <a href="{{ route('admin.jobs') }}" class="text-sm text-[#1E3D84] hover:underline font-medium">View All →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Client</th>
                    <th class="px-5 py-3 font-medium">Location</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($recentJobs as $job)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $job->category?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $job->client?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ Str::limit($job->location ?? '—', 30) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $job->status === 'completed' ? 'bg-green-50 text-green-700' :
                                   ($job->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700') }}">
                                {{ ucfirst($job->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $job->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No jobs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
