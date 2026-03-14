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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- User Info --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="text-center mb-4">
                <div class="w-16 h-16 rounded-full bg-[#1E3D84] text-white flex items-center justify-center text-xl font-bold mx-auto mb-3">
                    {{ strtoupper(substr($user->full_name, 0, 1)) }}
                </div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $user->full_name }}</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                    {{ $user->role === 'artisan' ? 'bg-orange-50 text-orange-700' : 'bg-emerald-50 text-emerald-700' }}">
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
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                        {{ ucfirst(str_replace('_', ' ', $user->account_status)) }}
                    </span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Registered</span>
                    <span class="font-medium text-gray-900">{{ $user->created_at?->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Rejection Reason (if previously rejected) --}}
        @if ($user->rejection_reason)
            <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                <h4 class="text-sm font-semibold text-red-800 mb-1">Previous Rejection Reason</h4>
                <p class="text-sm text-red-700">{{ $user->rejection_reason }}</p>
            </div>
        @endif
    </div>

    {{-- Documents & Actions --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Documents --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Submitted Documents</h3>

            @php $docs = $user->verificationDocuments; @endphp

            @if ($docs && $docs->count() > 0)
                @foreach ($docs as $doc)
                    <div class="space-y-4 mb-6 p-4 bg-gray-50 rounded-lg {{ !$loop->last ? 'border-b border-gray-200 pb-6' : '' }}">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Submission #{{ $loop->iteration }}</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @if ($doc->id_document_url)
                                <div>
                                    <p class="text-xs text-gray-500 mb-1.5">ID Document</p>
                                    <a href="{{ $doc->id_document_url }}" target="_blank"
                                       class="group relative block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                        <img src="{{ $doc->id_document_url }}" alt="ID Document"
                                             class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition">
                                            <x-admin-icon name="external-link" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition" />
                                        </div>
                                    </a>
                                </div>
                            @endif

                            @if ($doc->certification_url)
                                <div>
                                    <p class="text-xs text-gray-500 mb-1.5">Certification</p>
                                    <a href="{{ $doc->certification_url }}" target="_blank"
                                       class="group relative block aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                        <img src="{{ $doc->certification_url }}" alt="Certification"
                                             class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition">
                                            <x-admin-icon name="external-link" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition" />
                                        </div>
                                    </a>
                                </div>
                            @endif

                            @if ($doc->portfolio_url)
                                <div class="sm:col-span-2">
                                    <p class="text-xs text-gray-500 mb-1.5">Portfolio</p>
                                    <a href="{{ $doc->portfolio_url }}" target="_blank"
                                       class="group relative block aspect-[3/1] rounded-lg overflow-hidden bg-gray-100 border border-gray-200">
                                        <img src="{{ $doc->portfolio_url }}" alt="Portfolio"
                                             class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition">
                                            <x-admin-icon name="external-link" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition" />
                                        </div>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-sm text-gray-400 py-6 text-center">No documents submitted.</p>
            @endif
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="flex flex-col sm:flex-row gap-3">
                {{-- Approve --}}
                <form method="POST" action="{{ route('admin.verifications.approve', $user->id) }}" class="flex-1">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl
                                   bg-green-600 text-white font-semibold hover:bg-green-700 transition shadow-sm"
                            onclick="return confirm('Approve verification for {{ $user->full_name }}?')">
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
    </div>
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
</script>
@endsection
