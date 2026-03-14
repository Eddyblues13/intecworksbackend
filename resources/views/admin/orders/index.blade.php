@extends('admin.layouts.app')

@section('heading', 'Orders')
@section('subheading', 'Monitor material orders across the platform.')

@section('content')
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.orders') }}" class="flex flex-wrap items-center gap-3">
        <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#1E3D84]/20">
            <option value="">All Statuses</option>
            @foreach (['pending','confirmed','out_for_delivery','delivered','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-[#1E3D84] text-white text-sm font-medium hover:bg-[#0F2556] transition">Filter</button>
        @if (request('status'))
            <a href="{{ route('admin.orders') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left text-xs uppercase tracking-wider">
                    <th class="px-5 py-3 font-medium">ID</th>
                    <th class="px-5 py-3 font-medium">Buyer</th>
                    <th class="px-5 py-3 font-medium">Supplier</th>
                    <th class="px-5 py-3 font-medium">Amount</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-medium text-gray-900">#{{ $order->id }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $order->materialRequest?->artisan?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-600">{{ $order->supplier?->full_name ?? '—' }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900">₦{{ number_format($order->total_amount ?? 0, 2) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $order->status === 'delivered' ? 'bg-green-50 text-green-700' :
                                   ($order->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ ucfirst(str_replace('_',' ',$order->status)) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ $order->created_at?->format('M d') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-[#1E3D84] hover:bg-gray-100 transition inline-block">
                                <x-admin-icon name="eye" class="w-4 h-4" />
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($orders->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
