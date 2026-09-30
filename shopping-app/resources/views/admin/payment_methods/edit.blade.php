@extends('layouts.app')
@section('title', 'Edit ' . $paymentMethod->name)

@section('content')
    <h1 class="text-2xl font-bold mb-6 mt-6">Edit payment method</h1>

    <form action="{{ route('admin.paymentMethods.update', $paymentMethod) }}" method="POST" enctype="multipart/form-data"
          class="bg-white border rounded-lg p-6 max-w-lg space-y-4">
        @csrf
        @method('PUT')
        @include('admin.payment_methods._form', ['paymentMethod' => $paymentMethod])

        <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-md hover:bg-orange-700 font-semibold">
            Save changes
        </button>
    </form>
@endsection
