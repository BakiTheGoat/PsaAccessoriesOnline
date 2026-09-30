{{-- Shared fields for payment_methods/create and edit --}}

<div>
    <label class="block text-sm font-medium mb-1">Name</label>
    <input type="text" name="name" value="{{ old('name', $paymentMethod->name ?? '') }}" required
           placeholder="e.g. ABA Pay (KHQR), Cash on delivery"
           class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
</div>

<div>
    <label class="block text-sm font-medium mb-1">Instructions (optional)</label>
    <textarea name="instructions" rows="2" placeholder="e.g. Scan the QR code and pay the exact total, then upload your screenshot."
              class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">{{ old('instructions', $paymentMethod->instructions ?? '') }}</textarea>
</div>

@isset($paymentMethod)
    @if($paymentMethod->qr_url)
        <div>
            <label class="block text-sm font-medium mb-2">Current QR code</label>
            <img src="{{ $paymentMethod->qr_url }}" class="w-32 h-32 object-contain border rounded-md">
        </div>
    @endif
@endisset

<div>
    <label class="block text-sm font-medium mb-1">
        {{ isset($paymentMethod) ? 'Replace QR code image (optional)' : 'QR code image (optional — leave blank for cash)' }}
    </label>
    <input type="file" name="qr_image" accept="image/*"
           class="w-full border rounded-md px-3 py-2 bg-white">
</div>

<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $paymentMethod->is_active ?? true))>
    Active (buyers can select this at checkout)
</label>
