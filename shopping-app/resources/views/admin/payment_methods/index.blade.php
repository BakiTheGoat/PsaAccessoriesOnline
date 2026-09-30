@extends('layouts.app')
@section('title', 'Payment methods')

@section('content')
    <div class="flex items-center justify-between mb-6 mt-6">
        <h1 class="text-2xl font-bold">Payment methods</h1>
        <a href="{{ route('admin.paymentMethods.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 font-semibold">
            + Add payment method
        </a>
    </div>

    <p class="text-sm text-gray-500 mb-4">These are what buyers see and scan at checkout. Upload your shop's real QR code for each one (ABA Pay, ACLEDA, Wing, etc.) — "Cash on delivery" needs no QR.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @foreach($paymentMethods as $method)
            <div class="bg-white border rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-semibold text-gray-900">{{ $method->name }}</p>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $method->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $method->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @if($method->qr_url)
                    <img src="{{ $method->qr_url }}" class="w-full aspect-square object-contain border rounded-md mb-2">
                @else
                    <div class="w-full aspect-square bg-gray-50 border rounded-md flex items-center justify-center text-gray-300 mb-2">
                        No QR needed
                    </div>
                @endif
                @if($method->instructions)
                    <p class="text-xs text-gray-400 mb-2">{{ $method->instructions }}</p>
                @endif
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('admin.paymentMethods.edit', $method) }}" class="text-orange-600 hover:underline font-medium">Edit</a>
                    <form action="{{ route('admin.paymentMethods.destroy', $method) }}" method="POST"
                          onsubmit="return confirm('Remove this payment method?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Remove</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection
