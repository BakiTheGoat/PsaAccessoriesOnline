@extends('layouts.app')
@section('title', 'Add payment method')

@section('content')
    <h1 class="text-2xl font-bold mb-6 mt-6">Add payment method</h1>

    <form action="{{ route('admin.paymentMethods.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white border rounded-lg p-6 max-w-lg space-y-4">
        @csrf
        @include('admin.payment_methods._form')

        <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-md hover:bg-orange-700 font-semibold">
            Add payment method
        </button>
    </form>
@endsection
