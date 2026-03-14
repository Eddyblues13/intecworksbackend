@extends('admin.layouts.app')

@section('heading', 'Verifications')
@section('subheading', 'Manage OTP verification, document review, and all user verification stages.')

@section('content')
{{-- Stage Filter Tabs --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.verifications') }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                  {{ !request('stage') ? 'bg-[#1E3D84] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            All Pending
            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-bold
                         {{ !request('stage') ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700' }}">
                {{ $stageCounts['all'] }}
            </span>
        </a>
        <a href="{{ route('admin.verifications', ['stage' => 'otp_pending']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                  {{ request('stage') === 'otp_pending' ? 'bg-orange-500 text-white' : 'bg-orange-50 text-orange-700 hover:bg-orange-100' }}">
            <x-admin-icon name="phone" class="w-3.5 h-3.5 inline -mt-0.5" />
            OTP Pending
            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-bold
                         {{ request('stage') === 'otp_pending' ? 'bg-white/20 text-white' : 'bg-orange-100 text-orange-700' }}">
                {{ $stageCounts['otp_pending'] }}
            </span>
        </a>
        <a href="{{ route('admin.verifications', ['stage' => 'verification_pending']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                  {{ request('stage') === 'verification_pending' ? 'bg-yellow-500 text-white' : 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100' }}">
            <x-admin-icon name="clock" class="w-3.5 h-3.5 inline -mt-0.5" />
            Awaiting Docs
            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-bold
                         {{ request('stage') === 'verification_pending' ? 'bg-white/20 text-white' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $stageCounts['verification_pending'] }}
            </span>
        </a>
        <a href="{{ route('admin.verifications', ['stage' => 'verification_under_review']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                  {{ request('stage') === 'verification_under_review' ? 'bg-blue-500 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
            <x-admin-icon name="eye" class="w-3.5 h-3.5 inline -mt-0.5" />
            Under Review
            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-bold
                         {{ request('stage') === 'verification_under_review' ? 'bg-white/20 text-white' : 'bg-blue-100 text-blue-700' }}">
                {{ $stageCounts['verification_under_review'] }}
            </span>
        </a>
        <a href="{{ route('admin.verifications', ['stage' => 'rejected']) }}"
           class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                  {{ request('stage') === 'rejected' ? 'bg-red-500 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
            <x-admin-icon name="x-circle" class="w-3.5 h-3.5 inline -mt-0.5" />
            Rejected
            <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[10px] font-bold
                         {{ request('stage') === 'rejected' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-700' }}">
                {{ $stageCounts['rejected'] }}
            </span>
        </a>
    </div>
</div>

{{-- Search & Role Filters --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.verifications') }}" class="flex flex-wrap items-center gap-3">
        @if (request('stage'))
            <input type="hidden" name="stage" value="{{ request('stage') }}">
        @endif
        <div class="relative flex-1 min-w-[200px]">
            <x-admin-icon name="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, or phone…"
                   class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84]">
        </div>
        <select name="role" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84]">
            <option value="">All Roles</option>
            <option value="client" {{ request('role') === 'client' ? 'selected' : '' }}>Clients</option>
            <option value="artisan" {{ request('role') === 'artisan' ? 'selected' : '' }}>Artisans</option>
            <option value="supplier" {{ request('role') === 'supplier' ? 'selected' : '' }}>Suppliers</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-[#1E3D84] text-white text-sm font-medium rounded-lg hover:bg-[#0F2556] transition">
            Filter
        </button>
        @if (request('search') || request('role'))
            <a href="{{ route('admin.verifications', request('stage') ? ['stage' => request('stage')] : []) }}"
               class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                Clear
            </a>
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
                    <th class="px-5 py-3 font-medium">OTP Status</th>
                    <th class="px-5 py-3 font-medium">Account Stage</th>
                    <th class="px-5 py-3 font-medium">Documents</th>
                    <th class="px-5 py-3 font-medium">Registered</th>
                    <th class="px-5 py-3 font-medium text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($users as $user)
                    @php
                        $otpLatest = $user->otpVerifications->first();
                        $hasDocuments = $user->verificationDocuments->count() > 0;
                        $phoneVerified = $user->phone_verified_at !== null;

                        $stageConfig = [
                            'otp_pending'               => ['bg-orange-50 text-orange-700', 'OTP Pending'],
                            'verification_pending'      => ['bg-yellow-50 text-yellow-700', 'Awaiting Docs'],
                            'verification_under_review' => ['bg-blue-50 text-blue-700', 'Under Review'],
                            'rejected'                  => ['bg-red-50 text-red-700', 'Rejected'],
                        ];
                        $stageStyle = $stageConfig[$user->account_status] ?? ['bg-gray-100 text-gray-600', ucfirst(str_replace('_', ' ', $user->account_status))];
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#1E3D84]/10 text-[#1E3D84] flex items-center justify-center text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">{{ $user->full_name }}</span>
                                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            @php
                                $roleColors = [
                                    'client'   => 'bg-sky-50 text-sky-700',
                                    'artisan'  => 'bg-orange-50 text-orange-700',
                                    'supplier' => 'bg-emerald-50 text-emerald-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $user->phone }}</td>
                        <td class="px-5 py-3">
                            @if ($phoneVerified)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                    <x-admin-icon name="check-circle" class="w-3 h-3" /> Verified
                                </span>
                            @elseif ($otpLatest && $otpLatest->status === 'expired')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                    <x-admin-icon name="clock" class="w-3 h-3" /> Expired
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700">
                                    <x-admin-icon name="clock" class="w-3 h-3" /> Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $stageStyle[0] }}">
                                {{ $stageStyle[1] }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            @if ($hasDocuments)
                                <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                                    <x-admin-icon name="file-text" class="w-3 h-3" />
                                    {{ $user->verificationDocuments->count() }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">None</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $user->created_at?->format('M d, Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($user->account_status === 'otp_pending')
                                    <form method="POST" action="{{ route('admin.users.verify-otp', $user->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="Bypass OTP"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 transition"
                                                onclick="return confirm('Manually verify OTP for {{ $user->full_name }}? This will bypass the SMS verification.')">
                                            <x-admin-icon name="zap" class="w-3 h-3" />
                                            Verify OTP
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.verifications.show', $user->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
                                          bg-[#1E3D84] text-white hover:bg-[#0F2556] transition">
                                    <x-admin-icon name="eye" class="w-3.5 h-3.5" />
                                    Review
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                            <x-admin-icon name="check-circle" class="w-8 h-8 mx-auto mb-2 text-gray-300" />
                            <p>No users pending verification in this stage.</p>
                        </td>
                    </tr>
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
