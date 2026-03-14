@extends('admin.layouts.app')

@section('heading', 'Dispute #' . $dispute->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.disputes') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <x-admin-icon name="chevron-left" class="w-4 h-4" /> Back to Disputes
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Dispute Details</h3>
        <div class="space-y-4 text-sm">
            <div>
                <span class="text-gray-500">Reason</span>
                <p class="mt-1 text-gray-900">{{ $dispute->reason }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-500">Reported By</span>
                    @if ($dispute->reportedBy)
                        <a href="{{ route('admin.users.show', $dispute->reported_by_id) }}" class="block mt-0.5 font-medium text-[#1E3D84] hover:underline">{{ $dispute->reportedBy->full_name }}</a>
                    @else
                        <p class="mt-0.5 text-gray-400">—</p>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">Against</span>
                    @if ($dispute->against)
                        <a href="{{ route('admin.users.show', $dispute->against_id) }}" class="block mt-0.5 font-medium text-[#1E3D84] hover:underline">{{ $dispute->against->full_name }}</a>
                    @else
                        <p class="mt-0.5 text-gray-400">—</p>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">Job ID</span>
                    @if ($dispute->service_job_id)
                        <a href="{{ route('admin.jobs.show', $dispute->service_job_id) }}" class="block mt-0.5 font-medium text-[#1E3D84] hover:underline">Job #{{ $dispute->service_job_id }}</a>
                    @else
                        <p class="mt-0.5 text-gray-400">—</p>
                    @endif
                </div>
                <div><span class="text-gray-500">Filed</span><p class="mt-0.5 text-gray-900">{{ $dispute->created_at?->format('M d, Y H:i') }}</p></div>
            </div>

            @if ($dispute->resolution || $dispute->admin_notes)
                <div class="mt-4 p-4 rounded-lg bg-gray-50">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Resolution</p>
                    <p class="text-gray-900 font-medium">{{ ucfirst($dispute->resolution ?? $dispute->status) }}</p>
                    @if ($dispute->admin_notes)
                        <p class="text-gray-600 mt-1">{{ $dispute->admin_notes }}</p>
                    @endif
                    @if ($dispute->resolvedBy)
                        <p class="text-xs text-gray-500 mt-2">Resolved by {{ $dispute->resolvedBy->full_name }} on {{ $dispute->resolved_at?->format('M d, Y') }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 text-sm mb-3">Status</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $dispute->status === 'resolved' ? 'bg-green-50 text-green-700' :
                   ($dispute->status === 'dismissed' ? 'bg-gray-100 text-gray-600' : 'bg-red-50 text-red-700') }}">
                {{ ucfirst($dispute->status) }}
            </span>
        </div>

        @if (!in_array($dispute->status, ['resolved','dismissed']))
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 text-sm mb-3">Resolve Dispute</h3>
                <form method="POST" action="{{ route('admin.disputes.resolve', $dispute->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Resolution</label>
                        <select name="resolution" required
                                class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20">
                            <option value="resolved">Resolved</option>
                            <option value="dismissed">Dismissed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optional)</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20"
                                  placeholder="Admin notes…"></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
                        Submit Resolution
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
