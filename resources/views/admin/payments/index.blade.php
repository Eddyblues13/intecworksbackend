@extends('admin.layouts.app')

@section('heading', 'Payments')
@section('subheading', 'Monitor all payment transactions.')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.payments') }}" class="flex flex-wrap items-center gap-3">
        <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20">
            <option value="">All Statuses</option>
            @foreach (['pending','completed','failed','refunded'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">Filter</button>
        @if (request('status'))
            <a href="{{ route('admin.payments') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">Reference</th>
                    <th class="px-5 py-3 font-medium">User</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Method</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($payments as $payment)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-mono text-xs text-gray-700">{{ $payment->reference ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $payment->payer?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900">₦{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ ucfirst($payment->method ?? 'card') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $payment->status === 'completed' ? 'bg-green-50 text-green-700' :
                                   ($payment->status === 'refunded' ? 'bg-purple-50 text-purple-700' :
                                   ($payment->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700')) }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $payment->created_at?->format('M d') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.payments.show', $payment->id) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-[#1E3D84] hover:bg-gray-100 transition inline-block">
                                <x-admin-icon name="eye" class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($payments->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $payments->links() }}</div>
    @endif
</div>
@endsection
