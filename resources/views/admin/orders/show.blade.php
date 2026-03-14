@extends('admin.layouts.app')

@section('heading', 'Order #' . $order->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.orders') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
        <x-admin-icon name="chevron-left" class="w-4 h-4" /> Back to Orders
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-900 mb-4">Order Details</h3>
        <div class="grid grid-cols-2 gap-4 text-sm mb-6">
            <div><span class="text-gray-500">Buyer</span><p class="mt-0.5 font-medium text-gray-900">{{ $order->materialRequest?->artisan?->full_name ?? '—' }}</p></div>
            <div><span class="text-gray-500">Supplier</span><p class="mt-0.5 font-medium text-gray-900">{{ $order->supplier?->full_name ?? '—' }}</p></div>
            <div><span class="text-gray-500">Total Amount</span><p class="mt-0.5 font-medium text-gray-900">₦{{ number_format($order->total_amount ?? 0, 2) }}</p></div>
            <div><span class="text-gray-500">Payment Status</span><p class="mt-0.5 font-medium text-gray-900">{{ ucfirst($order->payment_status ?? 'pending') }}</p></div>
        </div>

        @if ($order->items && $order->items->count() > 0)
            <h4 class="font-medium text-gray-900 text-sm mb-2">Items</h4>
            <table class="w-full text-sm">
                <thead><tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-4 py-2 text-left font-medium">Material</th>
                    <th class="px-4 py-2 text-right font-medium">Qty</th>
                    <th class="px-4 py-2 text-right font-medium">Price</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="px-4 py-2 text-gray-900">{{ $item->material_name ?? $item->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ $item->quantity ?? 0 }}</td>
                            <td class="px-4 py-2 text-right text-gray-900">₦{{ number_format($item->price ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h3 class="font-semibold text-gray-900 mb-3 text-sm">Status</h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $order->status === 'delivered' ? 'bg-green-50 text-green-700' :
                   ($order->status === 'cancelled' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                {{ ucfirst(str_replace('_',' ',$order->status)) }}
            </span>
            <p class="mt-3 text-sm text-gray-500">Created {{ $order->created_at?->format('M d, Y H:i') }}</p>
        </div>

        @if (!in_array($order->status, ['cancelled','delivered']))
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <form method="POST" action="{{ route('admin.orders.cancel', $order->id) }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-red-50 text-red-700 text-sm font-medium hover:bg-red-100 transition"
                            onclick="return confirm('Cancel this order?')">
                        Cancel Order
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
