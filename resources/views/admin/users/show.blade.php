@extends('admin.layouts.app')

@section('heading', $user->full_name)
@section('subheading', ucfirst($user->role) . ' · Registered ' . $user->created_at?->format('M d, Y'))

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <x-admin-icon name="chevron-left" class="w-4 h-4" />
        Back to Users
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Profile Card --}}
    <div class="space-y-6">
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
                    {{ $user->role === 'client' ? 'bg-sky-50 text-sky-700' :
                       ($user->role === 'artisan' ? 'bg-orange-50 text-orange-700' : 'bg-emerald-50 text-emerald-700') }}">
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
                </div>
                <div class="flex justify-between py-2 border-b border-gray-50">
                    <span class="text-gray-500">Trust Score</span>
                    <span class="font-medium text-gray-900">{{ $user->trust_score ?? 0 }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Jobs</span>
                    <span class="font-medium text-gray-900">{{ $jobsCount }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm">Actions</h3>

            @if ($user->account_status === 'active')
                <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}">
                    @csrf
                    <button type="submit"
                            class="w-full px-4 py-2.5 rounded-lg bg-amber-50 text-amber-700 text-sm font-medium hover:bg-amber-100 transition"
                            onclick="return confirm('Suspend this user?')">
                        Suspend User
                    </button>
                </form>
            @elseif ($user->account_status === 'suspended')
                <form method="POST" action="{{ route('admin.users.activate', $user->id) }}">
                    @csrf
                    <button type="submit"
                            class="w-full px-4 py-2.5 rounded-lg bg-green-50 text-green-700 text-sm font-medium hover:bg-green-100 transition"
                            onclick="return confirm('Activate this user?')">
                        Activate User
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('admin.users.delete', $user->id) }}">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-lg bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100 transition"
                        onclick="return confirm('Are you sure you want to permanently delete {{ $user->full_name }}? This cannot be undone.')">
                    Delete User
                </button>
            </form>
        </div>
    </div>

    {{-- Verification Documents --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Verification Documents</h3>

            @php $docs = $user->verificationDocuments; @endphp

            @if ($docs && $docs->count() > 0)
                @foreach ($docs as $doc)
                    <div class="space-y-4 mb-6 p-4 bg-gray-50 rounded-lg">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Submission #{{ $loop->iteration }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @if ($doc->id_document_url)
                                <div>
                                    <p class="text-xs text-gray-500 mb-1.5">ID Document</p>
                                    <a href="{{ $doc->id_document_url }}" target="_blank" class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                        <img src="{{ $doc->id_document_url }}" alt="ID" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-full text-gray-400 text-sm\'>Unable to load</div>'">
                                    </a>
                                </div>
                            @endif
                            @if ($doc->certification_url)
                                <div>
                                    <p class="text-xs text-gray-500 mb-1.5">Certification</p>
                                    <a href="{{ $doc->certification_url }}" target="_blank" class="block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                        <img src="{{ $doc->certification_url }}" alt="Cert" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-full text-gray-400 text-sm\'>Unable to load</div>'">
                                    </a>
                                </div>
                            @endif
                            @if ($doc->portfolio_url)
                                <div class="sm:col-span-2">
                                    <p class="text-xs text-gray-500 mb-1.5">Portfolio</p>
                                    <a href="{{ $doc->portfolio_url }}" target="_blank" class="block aspect-[3/1] rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                        <img src="{{ $doc->portfolio_url }}" alt="Portfolio" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\'flex items-center justify-center h-full text-gray-400 text-sm\'>Unable to load</div>'">
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-400 py-8 text-center">No verification documents found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
