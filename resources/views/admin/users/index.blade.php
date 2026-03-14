@extends('admin.layouts.app')

@section('heading', 'Users')
@section('subheading', 'Manage all registered users on the platform.')

@section('content')
{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.users') }}" class="flex flex-wrap items-center gap-3">
        {{-- Search --}}
        <div class="flex-1 min-w-[200px]">
            <div class="relative">
                <x-admin-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, email, or phone…"
                       class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm
                              focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
            </div>
        </div>

        {{-- Role filter --}}
        <select name="role"
                class="px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700
                       focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
            <option value="">All Roles</option>
            <option value="client"   {{ request('role') === 'client'   ? 'selected' : '' }}>Client</option>
            <option value="artisan"  {{ request('role') === 'artisan'  ? 'selected' : '' }}>Artisan</option>
            <option value="supplier" {{ request('role') === 'supplier' ? 'selected' : '' }}>Supplier</option>
        </select>

        {{-- Status filter --}}
        <select name="status"
                class="px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700
                       focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84] transition">
            <option value="">All Statuses</option>
            <option value="active"                    {{ request('status') === 'active'                    ? 'selected' : '' }}>Active</option>
            <option value="suspended"                 {{ request('status') === 'suspended'                 ? 'selected' : '' }}>Suspended</option>
            <option value="verification_pending"      {{ request('status') === 'verification_pending'      ? 'selected' : '' }}>Verification Pending</option>
            <option value="verification_under_review" {{ request('status') === 'verification_under_review' ? 'selected' : '' }}>Under Review</option>
            <option value="rejected"                  {{ request('status') === 'rejected'                  ? 'selected' : '' }}>Rejected</option>
        </select>

        <button type="submit"
                class="px-4 py-2 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">
            <x-admin-icon name="filter" class="w-4 h-4 inline -mt-0.5" /> Filter
        </button>

        @if (request()->hasAny(['search', 'role', 'status']))
            <a href="{{ route('admin.users') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">User</th>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Phone</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Registered</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $user->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $user->role === 'client' ? 'bg-sky-50 text-sky-700' :
                                   ($user->role === 'artisan' ? 'bg-orange-50 text-orange-700' : 'bg-emerald-50 text-emerald-700') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $user->phone }}</td>
                        <td class="px-5 py-3">
                            @php
                                $statusColors = [
                                    'active'                    => 'bg-green-50 text-green-700',
                                    'suspended'                 => 'bg-red-50 text-red-700',
                                    'verification_pending'      => 'bg-yellow-50 text-yellow-700',
                                    'verification_under_review' => 'bg-amber-50 text-amber-700',
                                    'rejected'                  => 'bg-red-50 text-red-700',
                                    'otp_pending'               => 'bg-gray-100 text-gray-600',
                                    'subscription_required'     => 'bg-purple-50 text-purple-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $statusColors[$user->account_status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst(str_replace('_', ' ', $user->account_status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $user->created_at?->format('M d, Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.users.show', $user->id) }}"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-[#1E3D84] hover:bg-gray-100 transition"
                                   title="View">
                                    <x-admin-icon name="eye" class="w-4 h-4" />
                                </a>

                                @if ($user->account_status === 'active')
                                    <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition"
                                                title="Suspend" onclick="return confirm('Suspend {{ $user->full_name }}?')">
                                            <x-admin-icon name="alert-triangle" class="w-4 h-4" />
                                        </button>
                                    </form>
                                @elseif ($user->account_status === 'suspended')
                                    <form method="POST" action="{{ route('admin.users.activate', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition"
                                                title="Activate" onclick="return confirm('Activate {{ $user->full_name }}?')">
                                            <x-admin-icon name="check" class="w-4 h-4" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
