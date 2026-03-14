@extends('admin.layouts.app')

@section('heading', 'Notifications')
@section('subheading', 'Send broadcasts and view notification history.')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Send Broadcast --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Send Broadcast</h3>
        <form method="POST" action="{{ route('admin.notifications.broadcast') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                <input type="text" name="title" required
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20"
                       placeholder="Notification title">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Message</label>
                <textarea name="body" rows="3" required
                          class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20"
                          placeholder="Notification message…"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Target Audience</label>
                <select name="target_role"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20">
                    <option value="">All Users</option>
                    <option value="client">Clients</option>
                    <option value="artisan">Artisans</option>
                    <option value="supplier">Suppliers</option>
                </select>
            </div>
            <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
                <x-admin-icon name="send" class="w-4 h-4 inline -mt-0.5 mr-1" /> Send Broadcast
            </button>
        </form>
    </div>

    {{-- History --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Broadcast History</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse ($notifications as $notif)
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-gray-900 text-sm">{{ $notif->title }}</p>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $notif->body }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 shrink-0 ml-3">
                            {{ $notif->target_role ? ucfirst($notif->target_role) : 'All' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">{{ $notif->created_at?->diffForHumans() }}</p>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-gray-400">No broadcasts yet.</p>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
@endsection
