{{--
    Payment method selector — driven by the admin-managed PaymentMethod
    list. Expects $paymentMethods in scope. Each method optionally shows
    a QR code preview inline once selected (the buyer scans it AFTER
    placing the order, on the order receipt page — this just picks
    which one they intend to use).
--}}
<div class="bg-white border rounded-lg p-5">
    <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
        @include('partials.icon', ['name' => 'credit-card', 'class' => 'w-5 h-5 text-orange-600'])
        Payment method
    </h2>

    @if($paymentMethods->isEmpty())
        <p class="text-sm text-red-500">No payment methods are set up yet — ask an admin to add one before checking out.</p>
    @else
        <div class="space-y-2">
            @foreach($paymentMethods as $method)
                <label class="flex items-start gap-3 border rounded-md px-4 py-3 cursor-pointer hover:border-orange-400 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50">
                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" class="mt-1" @checked($loop->first) required>
                    <div class="flex-1">
                        <span class="text-sm font-medium">{{ $method->name }}</span>
                        @if($method->instructions)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $method->instructions }}</p>
                        @endif
                    </div>
                    @if($method->qr_url)
                        <img src="{{ $method->qr_url }}" class="w-12 h-12 object-cover rounded border">
                    @endif
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-3">After placing your order, you'll see the QR code to scan and a place to upload your payment screenshot.</p>
    @endif
</div>
