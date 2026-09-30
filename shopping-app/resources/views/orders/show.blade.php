@extends('layouts.app')
@section('title', 'Order #' . $order->id)

@section('content')
    @php
        $statusColors = [
            'pending' => 'bg-amber-100 text-amber-700',
            'payment_submitted' => 'bg-blue-100 text-blue-700',
            'paid' => 'bg-green-100 text-green-700',
            'completed' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-700',
        ];
        $steps = ['pending' => 0, 'payment_submitted' => 1, 'paid' => 2, 'completed' => 3];
        $currentStep = $steps[$order->status] ?? -1;
        $isBackOffice = auth()->user()->isBackOffice();
    @endphp

    <div class="max-w-2xl mx-auto mt-6">
        <a href="{{ $isBackOffice ? route('admin.orders.index') : route('dashboard') }}" class="text-sm text-orange-600 hover:underline">
            &larr; Back
        </a>

        <div class="bg-white border rounded-lg p-6 mt-4">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Order #{{ $order->id }}</h1>
                    <p class="text-xs text-gray-400">Placed {{ $order->created_at->format('M j, Y g:i A') }}</p>
                </div>
                <span class="text-xs font-bold px-3 py-1 rounded-full capitalize {{ $statusColors[$order->status] }}">
                    {{ $order->status_label }}
                </span>
            </div>

            {{-- ===================== Status tracker ===================== --}}
            @if($order->status !== 'cancelled')
                <div class="flex items-center mb-6">
                    @foreach(['Pending' => 0, 'Checking' => 1, 'Paid' => 2, 'Completed' => 3] as $label => $index)
                        <div class="flex items-center {{ $index < 3 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $index <= $currentStep ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-400' }}">
                                    @include('partials.icon', ['name' => 'check-circle', 'class' => 'w-5 h-5'])
                                </div>
                                <p class="text-[10px] mt-1 {{ $index <= $currentStep ? 'text-orange-600 font-semibold' : 'text-gray-400' }}">{{ $label }}</p>
                            </div>
                            @if($index < 3)
                                <div class="flex-1 h-0.5 mx-2 {{ $index < $currentStep ? 'bg-orange-600' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md px-4 py-3">
                    This order was cancelled.
                </div>
            @endif

            {{-- ===================== Items ===================== --}}
            <div class="divide-y border-t border-b">
                @foreach($order->items as $item)
                    @php $image = $item->product->images->first(); @endphp
                    <div class="flex gap-4 py-3">
                        @if($image)
                            <img src="{{ $image->url }}" class="w-16 h-16 object-cover rounded-md border">
                        @else
                            <div class="w-16 h-16 bg-gray-100 rounded-md border flex items-center justify-center text-gray-300">
                                @include('partials.icon', ['name' => 'photo', 'class' => 'w-6 h-6'])
                            </div>
                        @endif
                        <div class="flex-1">
                            <a href="{{ route('products.show', $item->product) }}" class="font-semibold text-gray-900 hover:text-orange-600 text-sm">
                                {{ $item->product->title }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">Qty: {{ $item->quantity }} &times; ${{ number_format($item->unit_price, 2) }}</p>
                        </div>
                        <p class="font-bold text-orange-600 text-sm">${{ number_format($item->subtotal, 2) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between items-center py-3 border-b">
                <p class="font-bold text-gray-900">Total</p>
                <p class="font-bold text-orange-600 text-lg">${{ number_format($order->total_price, 2) }}</p>
            </div>

            {{-- ===================== Shipping info ===================== --}}
            <div class="grid grid-cols-2 gap-4 mt-4 text-sm">
                <div>
                    <p class="text-gray-400 text-xs">Buyer</p>
                    <p class="font-medium">{{ $order->buyer->name }}</p>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Contact phone</p>
                    <p class="font-medium">{{ $order->shipping_phone }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-400 text-xs">Shipping to</p>
                    <p class="font-medium">{{ $order->shipping_name }} &mdash; {{ $order->shipping_address }}</p>
                    @if($order->delivery_latitude && $order->delivery_longitude)
                        <a href="https://www.openstreetmap.org/?mlat={{ $order->delivery_latitude }}&mlon={{ $order->delivery_longitude }}#map=16/{{ $order->delivery_latitude }}/{{ $order->delivery_longitude }}"
                           target="_blank" rel="noopener" class="text-xs text-orange-600 hover:underline inline-flex items-center gap-1 mt-1">
                            @include('partials.icon', ['name' => 'map-pin', 'class' => 'w-3 h-3'])
                            View pinned location on map
                        </a>
                    @endif
                </div>
            </div>

            {{-- ===================== Payment section ===================== --}}
            <div class="mt-6 border-t pt-4">
                <h3 class="font-semibold text-gray-900 mb-3">Payment — {{ $order->paymentMethod?->name ?? 'N/A' }}</h3>

                @if($order->status === 'pending')
                    @if($order->paymentMethod?->qr_url)
                        <div class="bg-gray-50 border rounded-md p-4 flex flex-col items-center text-center">
                            <img src="{{ $order->paymentMethod->qr_url }}" class="w-40 h-40 object-contain mb-3">
                            <p class="text-sm text-gray-600">Scan and pay exactly <span class="font-bold text-orange-600">${{ number_format($order->total_price, 2) }}</span></p>
                            <p class="text-xs text-gray-400 mt-1">Put <span class="font-mono font-semibold">Order #{{ $order->id }}</span> as the remark/note.</p>
                            @if($order->paymentMethod->instructions)
                                <p class="text-xs text-gray-400 mt-2">{{ $order->paymentMethod->instructions }}</p>
                            @endif
                        </div>
                    @endif

                    @if(auth()->id() === $order->buyer_id)
                        <form action="{{ route('orders.uploadScreenshot', $order) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <label class="block text-sm font-medium mb-1">Upload your payment screenshot</label>
                            <input type="file" name="payment_screenshot" accept="image/*" required
                                   class="w-full border rounded-md px-3 py-2 text-sm bg-white">
                            <button type="submit" class="mt-3 bg-orange-600 text-white text-sm px-4 py-2 rounded-md hover:bg-orange-700">
                                Send screenshot
                            </button>
                        </form>
                    @endif
                @elseif($order->status === 'payment_submitted')
                    <div class="bg-blue-50 border border-blue-200 text-blue-700 text-sm rounded-md px-4 py-3 mb-3">
                        We received your payment screenshot — checking it against our bank now. This usually doesn't take long.
                    </div>
                    @if($order->payment_screenshot_url)
                        <a href="{{ $order->payment_screenshot_url }}" target="_blank" rel="noopener">
                            <img src="{{ $order->payment_screenshot_url }}" class="w-32 h-32 object-cover rounded-md border">
                        </a>
                    @endif
                @elseif(in_array($order->status, ['paid', 'completed']))
                    <p class="text-sm text-green-600 font-semibold flex items-center gap-1">
                        @include('partials.icon', ['name' => 'check-circle', 'class' => 'w-4 h-4'])
                        Payment confirmed
                    </p>
                @endif
            </div>

            {{-- ===================== Staff/admin actions ===================== --}}
            @if($isBackOffice)
                <div class="mt-6 border-t pt-4 flex flex-wrap gap-2">
                    @if($order->status === 'payment_submitted')
                        @if(auth()->user()->isAdmin())
                            <form action="{{ route('orders.updateStatus', $order) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="paid">
                                <button class="bg-green-600 text-white text-sm px-4 py-2 rounded-md hover:bg-green-700">Approve payment</button>
                            </form>
                            <form action="{{ route('orders.updateStatus', $order) }}" method="POST">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="pending">
                                <button class="border border-amber-500 text-amber-600 text-sm px-4 py-2 rounded-md hover:bg-amber-50">Reject (back to pending)</button>
                            </form>
                        @else
                            <p class="text-xs text-gray-400 italic">Only an admin can approve or reject this payment.</p>
                        @endif
                    @endif
                    @if($order->status === 'paid')
                        <form action="{{ route('orders.updateStatus', $order) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button class="bg-green-600 text-white text-sm px-4 py-2 rounded-md hover:bg-green-700">Mark completed</button>
                        </form>
                    @endif
                    @if(in_array($order->status, ['pending', 'payment_submitted', 'paid']))
                        <form action="{{ route('orders.updateStatus', $order) }}" method="POST">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="border border-red-500 text-red-600 text-sm px-4 py-2 rounded-md hover:bg-red-50">Cancel order</button>
                        </form>
                    @endif
                </div>
            @elseif(auth()->id() === $order->buyer_id && $order->status === 'pending')
                <div class="mt-6 border-t pt-4">
                    <form action="{{ route('orders.updateStatus', $order) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button class="border border-red-500 text-red-600 text-sm px-4 py-2 rounded-md hover:bg-red-50">Cancel order</button>
                    </form>
                </div>
            @endif

            {{-- ===================== Leave a review ===================== --}}
            @if(auth()->id() === $order->buyer_id && $order->status === 'completed')
                @if($order->review)
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-900 mb-2">Your review</h3>
                        @include('partials.stars', ['rating' => $order->review->rating, 'count' => 1])
                        @if($order->review->comment)
                            <p class="text-sm text-gray-600 mt-2">{{ $order->review->comment }}</p>
                        @endif
                        @if($order->review->images->isNotEmpty())
                            <div class="flex gap-2 mt-3">
                                @foreach($order->review->images as $img)
                                    <a href="{{ $img->url }}" target="_blank" rel="noopener">
                                        <img src="{{ $img->url }}" class="w-16 h-16 object-cover rounded-md border">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mt-6 border-t pt-4">
                        <h3 class="font-semibold text-gray-900 mb-3">Leave a review</h3>
                        <form action="{{ route('reviews.store', $order) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="flex items-center gap-1 mb-3" id="star-picker">
                                @for($i = 1; $i <= 5; $i++)
                                    <button type="button" class="star-btn text-2xl text-gray-300 hover:text-amber-400" data-value="{{ $i }}">★</button>
                                @endfor
                                <input type="hidden" name="rating" id="rating-input" value="0" required>
                            </div>
                            <textarea name="comment" rows="3" placeholder="Optional: how was the order?"
                                      class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Add photos (optional, up to 5)</label>
                                <input type="file" name="images[]" multiple accept="image/*"
                                       class="w-full border rounded-md px-3 py-2 text-sm bg-white">
                            </div>
                            <button type="submit" class="mt-3 bg-orange-600 text-white text-sm px-4 py-2 rounded-md hover:bg-orange-700">
                                Submit review
                            </button>
                        </form>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const starButtons = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating-input');

    starButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const value = parseInt(btn.dataset.value, 10);
            ratingInput.value = value;
            starButtons.forEach((b) => {
                b.classList.toggle('text-amber-400', parseInt(b.dataset.value, 10) <= value);
                b.classList.toggle('text-gray-300', parseInt(b.dataset.value, 10) > value);
            });
        });
    });
</script>
@endpush
