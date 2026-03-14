@extends('admin.layouts.app')

@section('heading', 'Disputes')
@section('subheading', 'Manage user disputes and complaints.')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.disputes') }}" class="flex flex-wrap items-center gap-3">
        <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20">
            <option value="">All Statuses</option>
            @foreach (['open','resolved','dismissed'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">Filter</button>
        @if (request('status'))
            <a href="{{ route('admin.disputes') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">ID</th>
                    <th class="px-5 py-3 font-medium">Reported By</th>
                    <th class="px-5 py-3 font-medium">Against</th>
                    <th class="px-5 py-3 font-medium">Reason</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($disputes as $dispute)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-medium text-gray-900">#{{ $dispute->id }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $dispute->reportedBy?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $dispute->against?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ Str::limit($dispute->reason, 40) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $dispute->status === 'resolved' ? 'bg-green-50 text-green-700' :
                                   ($dispute->status === 'dismissed' ? 'bg-gray-100 text-gray-600' : 'bg-red-50 text-red-700') }}">
                                {{ ucfirst($dispute->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $dispute->created_at?->format('M d') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.disputes.show', $dispute->id) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-[#1E3D84] hover:bg-gray-100 transition inline-block">
                                <x-admin-icon name="eye" class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No disputes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($disputes->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $disputes->links() }}</div>
    @endif
</div>
@endsection
