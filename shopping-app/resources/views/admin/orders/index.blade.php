@extends('layouts.app')
@section('title', 'Manage orders')

@section('content')
    <h1 class="text-2xl font-bold mb-6 mt-6">Manage orders</h1>

    @php
        $statusColors = [
            'pending' => 'bg-amber-100 text-amber-700',
            'payment_submitted' => 'bg-blue-100 text-blue-700',
            'paid' => 'bg-green-100 text-green-700',
            'completed' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-700',
        ];
    @endphp

    <div class="bg-white border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="p-3">Order</th>
                    <th class="p-3">Customer</th>
                    <th class="p-3">Items</th>
                    <th class="p-3">Total</th>
                    <th class="p-3">Payment</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($orders as $order)
                    <tr class="{{ $order->status === 'payment_submitted' ? 'bg-blue-50/50' : '' }}">
                        <td class="p-3">#{{ $order->id }}</td>
                        <td class="p-3">{{ $order->buyer->name }}</td>
                        <td class="p-3">{{ $order->items->count() }} item(s)</td>
                        <td class="p-3">${{ number_format($order->total_price, 2) }}</td>
                        <td class="p-3">{{ $order->paymentMethod?->name ?? 'N/A' }}</td>
                        <td class="p-3">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $statusColors[$order->status] }}">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <a href="{{ route('orders.show', $order) }}" class="text-orange-600 hover:underline font-medium">
                                {{ $order->status === 'payment_submitted' ? 'Review payment' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
