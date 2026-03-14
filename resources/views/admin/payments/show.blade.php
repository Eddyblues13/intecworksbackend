@extends('admin.layouts.app')

@section('heading', 'Payment Detail')
@section('subheading', 'Reference: ' . ($payment->reference ?? '—'))

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.payments') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <x-admin-icon name="chevron-left" class="w-4 h-4" /> Back to Payments
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Payment Information</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">User</span><p class="mt-0.5 font-medium text-gray-900">{{ $payment->payer?->full_name ?? '—' }}</p></div>
            <div><span class="text-gray-500">Amount</span><p class="mt-0.5 font-medium text-gray-900 text-lg">₦{{ number_format($payment->amount, 2) }}</p></div>
            <div><span class="text-gray-500">Method</span><p class="mt-0.5 font-medium text-gray-900">{{ ucfirst($payment->method ?? 'card') }}</p></div>
            <div><span class="text-gray-500">Reference</span><p class="mt-0.5 font-mono text-xs text-gray-700">{{ $payment->reference ?? '—' }}</p></div>
            <div><span class="text-gray-500">Date</span><p class="mt-0.5 font-medium text-gray-900">{{ $payment->created_at?->format('M d, Y H:i') }}</p></div>
            <div><span class="text-gray-500">Status</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1
                    {{ $payment->status === 'completed' ? 'bg-green-50 text-green-700' :
                       ($payment->status === 'refunded' ? 'bg-purple-50 text-purple-700' : 'bg-amber-50 text-amber-700') }}">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        @if ($payment->status === 'completed')
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-semibold text-gray-900 text-sm mb-3">Issue Refund</h3>
                <form method="POST" action="{{ route('admin.payments.refund', $payment->id) }}">
                    @csrf
                    <div class="mb-3">
                        <input type="text" name="reason" placeholder="Refund reason"
                               class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20">
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-purple-50 text-purple-700 text-sm font-medium hover:bg-purple-100 transition"
                            onclick="return confirm('Issue a refund for ₦{{ number_format($payment->amount, 2) }}?')">
                        Issue Refund
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
