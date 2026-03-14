@extends('admin.layouts.app')

@section('heading', 'Jobs')
@section('subheading', 'Monitor and manage all service jobs on the platform.')

@section('content')
{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.jobs') }}" class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px]">
            <div class="relative">
                <x-admin-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search jobs…"
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm
                              focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
            </div>
        </div>
        <select name="status"
                class="px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700
                       focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
            <option value="">All Statuses</option>
            @foreach (['posted','accepted','in_progress','completed','cancelled','closed'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
            Filter
        </button>
        @if (request()->hasAny(['search','status']))
            <a href="{{ route('admin.jobs') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">Category</th>
                    <th class="px-5 py-3 font-medium">Client</th>
                    <th class="px-5 py-3 font-medium">Artisan</th>
                    <th class="px-5 py-3 font-medium">Location</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($jobs as $job)
                    <tr class="hover:bg-gray-50/50 {{ ($job->is_flagged ?? false) ? 'bg-red-50/30' : '' }}">
                        <td class="px-5 py-3 font-medium text-gray-900">
                            {{ $job->category?->name ?? '—' }}
                            @if ($job->is_flagged ?? false)
                                <span class="ml-1 text-red-500" title="Flagged">🚩</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-600">{{ $job->client?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $job->artisan?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ Str::limit($job->location ?? '—', 25) }}</td>
                        <td class="px-5 py-3">
                            @php
                                $jobColors = [
                                    'posted'      => 'bg-blue-50 text-blue-700',
                                    'accepted'    => 'bg-sky-50 text-sky-700',
                                    'in_progress' => 'bg-amber-50 text-amber-700',
                                    'completed'   => 'bg-green-50 text-green-700',
                                    'cancelled'   => 'bg-red-50 text-red-700',
                                    'closed'      => 'bg-gray-100 text-gray-600',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $jobColors[$job->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(str_replace('_',' ',$job->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $job->created_at?->format('M d') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.jobs.show', $job->id) }}"
                               class="p-1.5 rounded-lg text-gray-400 hover:text-[#1E3D84] hover:bg-gray-100 transition inline-block"
                               title="View">
                                <x-admin-icon name="eye" class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No jobs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($jobs->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $jobs->links() }}</div>
    @endif
</div>
@endsection
