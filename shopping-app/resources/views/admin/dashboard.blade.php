@extends('layouts.app')
@section('title', 'Admin dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-6 mt-6">
        {{ auth()->user()->isAdmin() ? 'Admin dashboard' : 'Staff dashboard' }}
    </h1>

    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
        <div class="bg-white border rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600">{{ $stats['users'] }}</p>
            <p class="text-sm text-gray-500">Customers</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600">{{ $stats['staff'] }}</p>
            <p class="text-sm text-gray-500">Staff accounts</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600">{{ $stats['products'] }}</p>
            <p class="text-sm text-gray-500">Products listed</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-red-500">{{ $stats['out_of_stock'] }}</p>
            <p class="text-sm text-gray-500">Out of stock</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-orange-600">{{ $stats['orders'] }}</p>
            <p class="text-sm text-gray-500">Total orders</p>
        </div>
        <div class="bg-white border rounded-lg p-4 text-center">
            <p class="text-2xl font-bold text-amber-500">{{ $stats['payments_to_check'] }}</p>
            <p class="text-sm text-gray-500">Payments to check</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-4">
        <a href="{{ route('admin.orders.index') }}" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">
            Manage orders
        </a>
        <a href="{{ route('admin.products.index') }}" class="border border-orange-600 text-orange-600 px-4 py-2 rounded-md hover:bg-orange-50">
            Manage products
        </a>
        <a href="{{ route('admin.paymentMethods.index') }}" class="border border-orange-600 text-orange-600 px-4 py-2 rounded-md hover:bg-orange-50">
            Payment methods
        </a>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.users.index') }}" class="border border-orange-600 text-orange-600 px-4 py-2 rounded-md hover:bg-orange-50">
                Manage staff & customers
            </a>
        @endif
    </div>
@endsection
