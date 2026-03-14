@extends('admin.layouts.app')

@section('heading', 'Job Detail')
@section('subheading', ($job->category?->name ?? 'Job') . ' #' . $job->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.jobs') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <x-admin-icon name="chevron-left" class="w-4 h-4" /> Back to Jobs
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main Info --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Job Information</h3>
            <div class="space-y-4 text-sm">
                <div>
                    <span class="text-gray-500">Description</span>
                    <p class="mt-1 text-gray-900">{{ $job->description ?? '—' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-500">Category</span>
                        <p class="mt-1 font-medium text-gray-900">{{ $job->category?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Job Type</span>
                        <p class="mt-1 font-medium text-gray-900">{{ ucfirst(str_replace('_',' ',$job->job_type ?? '—')) }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Location</span>
                        <p class="mt-1 font-medium text-gray-900">{{ $job->location ?? '—' }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Progress</span>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full bg-[#1E3D84] rounded-full" style="width: {{ $job->progress_percent ?? 0 }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600">{{ $job->progress_percent ?? 0 }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($job->is_flagged ?? false)
                <div class="mt-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-sm font-medium text-red-800">🚩 Flagged</p>
                    <p class="text-sm text-red-700 mt-1">{{ $job->flagged_reason ?? 'No reason specified.' }}</p>
                </div>
            @endif
        </div>

        {{-- Photos --}}
        @if (!empty($job->images) || !empty($job->before_photos) || !empty($job->after_photos))
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-4">Photos</h3>
                @foreach (['images' => 'Job Images', 'before_photos' => 'Before Photos', 'after_photos' => 'After Photos'] as $field => $label)
                    @if (!empty($job->$field))
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 {{ !$loop->first ? 'mt-4' : '' }}">{{ $label }}</p>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                            @foreach ($job->$field as $photo)
                                <a href="{{ $photo }}" target="_blank" class="block aspect-square rounded-lg overflow-hidden bg-gray-100">
                                    <img src="{{ $photo }}" alt="" class="w-full h-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Status --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-3 text-sm">Status & Timeline</h3>
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
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mb-4
                {{ $jobColors[$job->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ ucfirst(str_replace('_',' ',$job->status)) }}
            </span>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="text-gray-900">{{ $job->created_at?->format('M d, Y') }}</span></div>
                @if ($job->accepted_at)<div class="flex justify-between"><span class="text-gray-500">Accepted</span><span class="text-gray-900">{{ $job->accepted_at->format('M d, Y') }}</span></div>@endif
                @if ($job->started_at)<div class="flex justify-between"><span class="text-gray-500">Started</span><span class="text-gray-900">{{ $job->started_at->format('M d, Y') }}</span></div>@endif
                @if ($job->completed_at)<div class="flex justify-between"><span class="text-gray-500">Completed</span><span class="text-gray-900">{{ $job->completed_at->format('M d, Y') }}</span></div>@endif
            </div>
        </div>

        {{-- People --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-3 text-sm">People</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-gray-500">Client</span>
                    @if ($job->client)
                        <a href="{{ route('admin.users.show', $job->client->id) }}" class="block mt-0.5 font-medium text-[#1E3D84] hover:underline">{{ $job->client->full_name }}</a>
                    @else
                        <p class="mt-0.5 text-gray-400">—</p>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">Artisan</span>
                    @if ($job->artisan)
                        <a href="{{ route('admin.users.show', $job->artisan->id) }}" class="block mt-0.5 font-medium text-[#1E3D84] hover:underline">{{ $job->artisan->full_name }}</a>
                    @else
                        <p class="mt-0.5 text-gray-400">Not assigned</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm">Actions</h3>

            @if (!($job->is_flagged ?? false))
                <form method="POST" action="{{ route('admin.jobs.flag', $job->id) }}" onsubmit="return handleFlag(this)">
                    @csrf
                    <input type="hidden" name="reason" id="flag-reason">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-amber-50 text-amber-700 text-sm font-medium hover:bg-amber-100 transition">
                        🚩 Flag Job
                    </button>
                </form>
            @endif

            @if (!in_array($job->status, ['cancelled','closed']))
                <form method="POST" action="{{ route('admin.jobs.remove', $job->id) }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100 transition"
                            onclick="return confirm('Cancel this job?')">
                        Cancel Job
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
function handleFlag(form) {
    const reason = prompt('Reason for flagging this job:');
    if (!reason || reason.trim().length < 3) {
        alert('Please provide a reason.');
        return false;
    }
    form.querySelector('input[name="reason"]').value = reason.trim();
    return true;
}
</script>
@endsection
