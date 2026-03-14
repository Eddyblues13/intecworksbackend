@extends('admin.layouts.app')

@section('heading', 'Activity Logs')
@section('subheading', 'Track all admin actions on the platform.')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">Admin</th>
                    <th class="px-5 py-3 font-medium">Action</th>
                    <th class="px-5 py-3 font-medium">Target</th>
                    <th class="px-5 py-3 font-medium">Details</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($logs as $log)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $log->admin?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @php
                                $actionColors = [
                                    'approved_verification' => 'bg-green-50 text-green-700',
                                    'rejected_verification' => 'bg-red-50 text-red-700',
                                    'suspended_user'        => 'bg-amber-50 text-amber-700',
                                    'activated_user'        => 'bg-green-50 text-green-700',
                                    'deleted_user'          => 'bg-red-50 text-red-700',
                                    'flagged_job'           => 'bg-amber-50 text-amber-700',
                                    'removed_job'           => 'bg-red-50 text-red-700',
                                    'cancelled_order'       => 'bg-red-50 text-red-700',
                                    'issued_refund'         => 'bg-purple-50 text-purple-700',
                                    'resolved_dispute'      => 'bg-green-50 text-green-700',
                                    'sent_broadcast'        => 'bg-blue-50 text-blue-700',
                                    'updated_settings'      => 'bg-gray-100 text-gray-600',
                                    'updated_profile'       => 'bg-gray-100 text-gray-600',
                                    'changed_password'      => 'bg-gray-100 text-gray-600',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $actionColors[$log->action] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">
                            @if ($log->target_type && $log->target_id)
                                {{ ucfirst($log->target_type) }} #{{ $log->target_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            @if ($log->details)
                                {{ Str::limit(json_encode($log->details), 50) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $log->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">No activity logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($logs->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
