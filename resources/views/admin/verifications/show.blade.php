@extends('admin.layouts.app')

@section('heading', 'Verification Review')
@section('subheading', $user->full_name . ' — ' . ucfirst($user->role))

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.verifications') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <x-admin-icon name="chevron-left" class="w-4 h-4" />
        Back to Verifications
    </a>
</div>

{{-- Verification Pipeline --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Verification Pipeline</h3>
    @php
        $stages = [
            'otp_pending'               => ['OTP / Phone', 'phone', 'Verify phone via OTP'],
            'verification_pending'      => ['Upload Documents', 'file-text', 'Awaiting document submission'],
            'verification_under_review' => ['Under Review', 'eye', 'Documents submitted, review required'],
            'active'                    => ['Approved', 'check-circle', 'Account is active'],
            'subscription_required'     => ['Subscription', 'credit-card', 'Needs subscription (suppliers)'],
        ];
        $statusOrder = ['otp_pending', 'verification_pending', 'verification_under_review', 'active', 'subscription_required'];
        $currentIndex = array_search($user->account_status, $statusOrder);
        if ($currentIndex === false) $currentIndex = -1;
        $isRejected = $user->account_status === 'rejected';
    @endphp
    <div class="flex items-center gap-0 overflow-x-auto pb-2">
        @foreach ($stages as $key => $info)
            @php
                $idx = array_search($key, $statusOrder);
                $isCompleted = !$isRejected && $currentIndex > $idx;
                $isCurrent = !$isRejected && $user->account_status === $key;
                $isFuture = !$isRejected && $currentIndex < $idx;
            @endphp
            @if (!$loop->first)
                <div class="w-8 h-0.5 shrink-0 {{ $isCompleted ? 'bg-green-400' : 'bg-gray-200' }}"></div>
            @endif
            <div class="flex flex-col items-center min-w-[100px] text-center">
                <div class="w-10 h-10 rounded-full flex items-center justify-center mb-1.5
                    {{ $isCompleted ? 'bg-green-100 text-green-700' :
                       ($isCurrent ? 'bg-[#1E3D84] text-white ring-4 ring-[#1E3D84]/20' :
                       ($isRejected && $key === 'verification_under_review' ? 'bg-red-100 text-red-600 ring-4 ring-red-100' : 'bg-gray-100 text-gray-400')) }}">
                    @if ($isCompleted)
                        <x-admin-icon name="check" class="w-5 h-5" />
                    @elseif ($isRejected && $key === 'verification_under_review')
                        <x-admin-icon name="x" class="w-5 h-5" />
                    @else
                        <x-admin-icon name="{{ $info[1] }}" class="w-5 h-5" />
                    @endif
                </div>
                <span class="text-[11px] font-medium {{ $isCurrent ? 'text-[#1E3D84]' : ($isCompleted ? 'text-green-700' : 'text-gray-400') }}">
                    {{ $info[0] }}
                </span>
            </div>
        @endforeach
    </div>
    @if ($isRejected)
        <div class="mt-3 px-4 py-2.5 bg-red-50 rounded-lg border border-red-200 flex items-start gap-2">
            <x-admin-icon name="alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-red-800">Verification Rejected</p>
                @if ($user->rejection_reason)
                    <p class="text-sm text-red-600 mt-0.5">{{ $user->rejection_reason }}</p>
                @endif
            </div>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left Column: User Info + OTP --}}
    <div class="space-y-6">
        {{-- User Info Card --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="text-center mb-4">
                @if ($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover">
                @else
                    <div class="w-16 h-16 rounded-full bg-[#1E3D84] text-white flex items-center justify-center text-xl font-bold mx-auto mb-3">
                        {{ strtoupper(substr($user->full_name, 0, 1)) }}
                    </div>
                @endif
                <h3 class="text-lg font-semibold text-gray-900">{{ $user->full_name }}</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                    {{ $user->role === 'artisan' ? 'bg-orange-50 text-orange-700' :
                       ($user->role === 'supplier' ? 'bg-emerald-50 text-emerald-700' : 'bg-sky-50 text-sky-700') }}">
                    {{ ucfirst($user->role) }}
                </span>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500">Email</span>
                    <span class="font-medium text-gray-900">{{ $user->email }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500">Phone</span>
                    <span class="font-medium text-gray-900">{{ $user->phone }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500">Phone Verified</span>
                    @if ($user->phone_verified_at)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                            <x-admin-icon name="check-circle" class="w-3 h-3" />
                            {{ $user->phone_verified_at->format('M d, Y H:i') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                            <x-admin-icon name="x-circle" class="w-3 h-3" /> Not Verified
                        </span>
                    @endif
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500">Location</span>
                    <span class="font-medium text-gray-900">{{ $user->location ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500">Status</span>
                    @php
                        $statusColors = [
                            'active'                    => 'bg-green-50 text-green-700',
                            'suspended'                 => 'bg-red-50 text-red-700',
                            'verification_pending'      => 'bg-yellow-50 text-yellow-700',
                            'verification_under_review' => 'bg-blue-50 text-blue-700',
                            'rejected'                  => 'bg-red-50 text-red-700',
                            'otp_pending'               => 'bg-orange-50 text-orange-700',
                            'subscription_required'     => 'bg-purple-50 text-purple-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $statusColors[$user->account_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst(str_replace('_', ' ', $user->account_status)) }}
                    </span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Registered</span>
                    <span class="font-medium text-gray-900">{{ $user->created_at?->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        {{-- OTP History --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <x-admin-icon name="phone" class="w-4 h-4 text-gray-400" />
                OTP Verification History
            </h3>
            @if ($otpHistory->count() > 0)
                <div class="space-y-3">
                    @foreach ($otpHistory as $otp)
                        <div class="p-3 rounded-lg border {{ $otp->status === 'verified' ? 'border-green-200 bg-green-50/50' : ($otp->status === 'expired' ? 'border-red-200 bg-red-50/50' : 'border-gray-200 bg-gray-50') }}">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium text-gray-700">{{ $otp->phone }}</span>
                                @php
                                    $otpStatusConfig = [
                                        'verified' => ['bg-green-100 text-green-700', 'Verified'],
                                        'expired'  => ['bg-red-100 text-red-700', 'Expired'],
                                        'pending'  => ['bg-amber-100 text-amber-700', 'Pending'],
                                    ];
                                    $otpStyle = $otpStatusConfig[$otp->status] ?? ['bg-gray-100 text-gray-600', ucfirst($otp->status)];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $otpStyle[0] }}">
                                    {{ $otpStyle[1] }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-gray-500">
                                <div>
                                    <span class="text-gray-400">Attempts:</span>
                                    <span class="font-medium text-gray-700">{{ $otp->attempts }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Expires:</span>
                                    <span class="font-medium text-gray-700">{{ $otp->expires_at?->format('M d H:i') }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-400">Created:</span>
                                    <span class="font-medium text-gray-700">{{ $otp->created_at?->format('M d, Y H:i') }}</span>
                                </div>
                                @if ($otp->pin_id)
                                    <div class="col-span-2">
                                        <span class="text-gray-400">Pin ID:</span>
                                        <span class="font-mono text-gray-600">{{ Str::limit($otp->pin_id, 20) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-4">No OTP records found.</p>
            @endif

            {{-- Manual OTP Verify Button --}}
            @if (!$user->phone_verified_at)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <form method="POST" action="{{ route('admin.users.verify-otp', $user->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg
                                       bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition"
                                onclick="return confirm('Manually verify phone/OTP for {{ $user->full_name }}? This bypasses the SMS flow and advances the account status.')">
                            <x-admin-icon name="zap" class="w-4 h-4" />
                            Manually Verify Phone / OTP
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Manual Status Advancement --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <x-admin-icon name="skip-forward" class="w-4 h-4 text-gray-400" />
                Advance Status
            </h3>
            <p class="text-xs text-gray-500 mb-3">Override the current account status. Use with caution.</p>
            <form method="POST" action="{{ route('admin.users.advance-status', $user->id) }}">
                @csrf
                <select name="new_status" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm mb-3
                               focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20 focus:border-[#1E3D84]">
                    <option value="">Select new status…</option>
                    <option value="otp_pending" {{ $user->account_status === 'otp_pending' ? 'disabled' : '' }}>OTP Pending</option>
                    <option value="verification_pending" {{ $user->account_status === 'verification_pending' ? 'disabled' : '' }}>Verification Pending</option>
                    <option value="verification_under_review" {{ $user->account_status === 'verification_under_review' ? 'disabled' : '' }}>Under Review</option>
                    <option value="approved">✅ Approved (Active / Subscription)</option>
                    <option value="active" {{ $user->account_status === 'active' ? 'disabled' : '' }}>Active</option>
                    @if ($user->role === 'supplier')
                        <option value="subscription_required" {{ $user->account_status === 'subscription_required' ? 'disabled' : '' }}>Subscription Required</option>
                    @endif
                    <option value="rejected" {{ $user->account_status === 'rejected' ? 'disabled' : '' }}>Rejected</option>
                    <option value="suspended" {{ $user->account_status === 'suspended' ? 'disabled' : '' }}>Suspended</option>
                </select>
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition"
                        onclick="return confirm('Are you sure you want to change this user\'s status?')">
                    Update Status
                </button>
            </form>
        </div>
    </div>

    {{-- Right Column: Documents & Actions --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Documents --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <x-admin-icon name="file-text" class="w-4 h-4 text-gray-400" />
                Submitted Documents
            </h3>

            @php $docs = $user->verificationDocuments; @endphp

            @if ($docs && $docs->count() > 0)
                @foreach ($docs as $doc)
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-100 {{ !$loop->last ? 'mb-6' : '' }}">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Submission #{{ $loop->iteration }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">{{ $doc->created_at?->format('M d, Y H:i') }}</p>
                            </div>
                            @php
                                $docStatusConfig = [
                                    'pending'  => ['bg-amber-100 text-amber-700', 'Pending Review'],
                                    'approved' => ['bg-green-100 text-green-700', 'Approved'],
                                    'rejected' => ['bg-red-100 text-red-700', 'Rejected'],
                                ];
                                $docStyle = $docStatusConfig[$doc->status] ?? ['bg-gray-100 text-gray-600', ucfirst($doc->status)];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $docStyle[0] }}">
                                {{ $docStyle[1] }}
                            </span>
                        </div>

                        @if ($doc->rejection_reason)
                            <div class="mb-3 px-3 py-2 bg-red-50 rounded-lg border border-red-200 text-sm text-red-700">
                                <span class="font-medium">Rejection reason:</span> {{ $doc->rejection_reason }}
                            </div>
                        @endif

                        @if ($doc->reviewed_at)
                            <p class="text-[11px] text-gray-400 mb-3">Reviewed: {{ $doc->reviewed_at->format('M d, Y H:i') }}</p>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- ID Document --}}
                            @if ($doc->id_document_url)
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <p class="text-xs font-medium text-gray-600">
                                            <x-admin-icon name="shield" class="w-3 h-3 inline text-gray-400" />
                                            ID Document
                                        </p>
                                        <a href="{{ $doc->id_document_url }}" target="_blank"
                                           class="text-[10px] text-[#1E3D84] hover:underline flex items-center gap-1">
                                            Full Size <x-admin-icon name="external-link" class="w-3 h-3" />
                                        </a>
                                    </div>
                                    <a href="{{ $doc->id_document_url }}" target="_blank"
                                       class="group relative block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200 hover:border-[#1E3D84]/30 transition">
                                        <img src="{{ $doc->id_document_url }}" alt="ID Document"
                                             class="w-full h-full object-cover transition group-hover:scale-105"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="absolute inset-0 items-center justify-center text-gray-400 text-sm" style="display:none;">
                                            <x-admin-icon name="image" class="w-8 h-8 text-gray-300" />
                                        </div>
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition">
                                            <x-admin-icon name="eye" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition drop-shadow" />
                                        </div>
                                    </a>
                                </div>
                            @endif

                            {{-- Certification --}}
                            @if ($doc->certification_url)
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <p class="text-xs font-medium text-gray-600">
                                            <x-admin-icon name="shield-check" class="w-3 h-3 inline text-gray-400" />
                                            Certification
                                        </p>
                                        <a href="{{ $doc->certification_url }}" target="_blank"
                                           class="text-[10px] text-[#1E3D84] hover:underline flex items-center gap-1">
                                            Full Size <x-admin-icon name="external-link" class="w-3 h-3" />
                                        </a>
                                    </div>
                                    <a href="{{ $doc->certification_url }}" target="_blank"
                                       class="group relative block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200 hover:border-[#1E3D84]/30 transition">
                                        <img src="{{ $doc->certification_url }}" alt="Certification"
                                             class="w-full h-full object-cover transition group-hover:scale-105"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="absolute inset-0 items-center justify-center text-gray-400 text-sm" style="display:none;">
                                            <x-admin-icon name="image" class="w-8 h-8 text-gray-300" />
                                        </div>
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition">
                                            <x-admin-icon name="eye" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition drop-shadow" />
                                        </div>
                                    </a>
                                </div>
                            @endif

                            {{-- Portfolio --}}
                            @if ($doc->portfolio_url)
                                <div class="sm:col-span-2">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <p class="text-xs font-medium text-gray-600">
                                            <x-admin-icon name="image" class="w-3 h-3 inline text-gray-400" />
                                            Portfolio
                                        </p>
                                        <a href="{{ $doc->portfolio_url }}" target="_blank"
                                           class="text-[10px] text-[#1E3D84] hover:underline flex items-center gap-1">
                                            Full Size <x-admin-icon name="external-link" class="w-3 h-3" />
                                        </a>
                                    </div>
                                    <a href="{{ $doc->portfolio_url }}" target="_blank"
                                       class="group relative block aspect-[3/1] rounded-lg overflow-hidden bg-gray-100 border border-gray-200 hover:border-[#1E3D84]/30 transition">
                                        <img src="{{ $doc->portfolio_url }}" alt="Portfolio"
                                             class="w-full h-full object-cover transition group-hover:scale-105"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="absolute inset-0 items-center justify-center text-gray-400 text-sm" style="display:none;">
                                            <x-admin-icon name="image" class="w-8 h-8 text-gray-300" />
                                        </div>
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition">
                                            <x-admin-icon name="eye" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition drop-shadow" />
                                        </div>
                                    </a>
                                </div>
                            @endif
                        </div>

                        @if (!$doc->id_document_url && !$doc->certification_url && !$doc->portfolio_url)
                            <p class="text-sm text-gray-400 text-center py-4">No document files in this submission.</p>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-10">
                    <x-admin-icon name="file-text" class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                    <p class="text-sm text-gray-400">No documents submitted yet.</p>
                    @if ($user->account_status === 'verification_pending')
                        <p class="text-xs text-gray-400 mt-1">This user still needs to upload their verification documents.</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Approve / Reject Actions --}}
        @if (in_array($user->account_status, ['verification_under_review', 'verification_pending']))
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <x-admin-icon name="shield-check" class="w-4 h-4 text-gray-400" />
                    Verification Decision
                </h3>
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Approve --}}
                    <form method="POST" action="{{ route('admin.verifications.approve', $user->id) }}" class="flex-1">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl
                                       bg-green-600 text-white font-semibold hover:bg-green-700 transition shadow-sm"
                                onclick="return confirm('Approve verification for {{ $user->full_name }}? They will gain full access.')">
                            <x-admin-icon name="check" class="w-4.5 h-4.5" />
                            Approve Verification
                        </button>
                    </form>

                    {{-- Reject --}}
                    <form method="POST" action="{{ route('admin.verifications.reject', $user->id) }}" class="flex-1"
                          onsubmit="return handleReject(this)">
                        @csrf
                        <input type="hidden" name="reason" id="reject-reason-{{ $user->id }}">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl
                                       bg-red-600 text-white font-semibold hover:bg-red-700 transition shadow-sm">
                            <x-admin-icon name="x" class="w-4.5 h-4.5" />
                            Reject Verification
                        </button>
                    </form>
                </div>
            </div>
        @elseif ($user->account_status === 'rejected')
            <div class="bg-amber-50 rounded-xl border border-amber-200 p-5">
                <div class="flex items-start gap-3">
                    <x-admin-icon name="alert-circle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-amber-800">This user was rejected</h3>
                        @if ($user->rejection_reason)
                            <p class="text-sm text-amber-700 mt-1">Reason: {{ $user->rejection_reason }}</p>
                        @endif
                        <p class="text-xs text-amber-600 mt-2">Use the "Advance Status" panel on the left to reactivate or reset the verification.</p>
                    </div>
                </div>
            </div>
        @elseif ($user->account_status === 'otp_pending')
            <div class="bg-orange-50 rounded-xl border border-orange-200 p-5">
                <div class="flex items-start gap-3">
                    <x-admin-icon name="phone" class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-orange-800">Awaiting Phone Verification</h3>
                        <p class="text-sm text-orange-700 mt-1">This user has not verified their phone number yet. You can manually verify their phone using the OTP panel on the left.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- View on User Page link --}}
        <div class="text-center">
            <a href="{{ route('admin.users.show', $user->id) }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-[#1E3D84] transition">
                <x-admin-icon name="user" class="w-4 h-4" />
                View Full User Profile
                <x-admin-icon name="arrow-right" class="w-4 h-4" />
            </a>
        </div>
    </div>
</div>

{{-- Image Lightbox --}}
<div id="lightbox" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm items-center justify-center p-4 cursor-zoom-out" onclick="closeLightbox()">
    <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl">
</div>

<script>
function handleReject(form) {
    const reason = prompt('Please provide a reason for rejection:');
    if (!reason || reason.trim().length < 5) {
        alert('Please provide a reason (at least 5 characters).');
        return false;
    }
    form.querySelector('input[name="reason"]').value = reason.trim();
    return true;
}

// Lightbox for document images
document.querySelectorAll('[data-lightbox]').forEach(el => {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('lightbox-img').src = this.href;
        document.getElementById('lightbox').classList.remove('hidden');
        document.getElementById('lightbox').classList.add('flex');
    });
});

function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.getElementById('lightbox').classList.remove('flex');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>
@endsection
