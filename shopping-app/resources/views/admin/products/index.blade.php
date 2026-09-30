@extends('layouts.app')
@section('title', 'Manage products')

@section('content')
    <div class="flex items-center justify-between mb-6 mt-6">
        <h1 class="text-2xl font-bold">Manage products</h1>
        <a href="{{ route('products.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700 font-semibold">
            + Post product
        </a>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="p-3">Title</th>
                    <th class="p-3">Price</th>
                    <th class="p-3">Condition</th>
                    <th class="p-3">Category</th>
                    <th class="p-3">Stock</th>
                    <th class="p-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($products as $product)
                    <tr>
                        <td class="p-3">
                            <a href="{{ route('products.show', $product) }}" class="hover:text-orange-600">{{ $product->title }}</a>
                        </td>
                        <td class="p-3">${{ number_format($product->price, 2) }}</td>
                        <td class="p-3 capitalize">{{ $product->condition }}</td>
                        <td class="p-3">{{ $product->category }}</td>
                        <td class="p-3">
                            @if($product->in_stock)
                                <span class="text-green-600 font-medium">{{ $product->stock_quantity }}</span>
                            @else
                                <span class="text-red-500 font-medium">Out of stock</span>
                            @endif
                        </td>
                        <td class="p-3 text-right space-x-3">
                            <a href="{{ route('products.edit', $product) }}" class="text-orange-600 hover:underline font-medium">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Remove this product?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
@endsection
