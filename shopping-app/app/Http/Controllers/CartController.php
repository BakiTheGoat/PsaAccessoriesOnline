<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /** View the cart — a single shop now, so it's just one flat list of items. */
    public function index(Request $request)
    {
        $items = $request->user()->cartItems()->with(['product.images'])->get();

        return view('cart.index', [
            'items' => $items,
            'total' => $items->sum(fn (CartItem $item) => $item->subtotal),
        ]);
    }

    /** Add a product to the cart (or bump its quantity), capped at available stock. */
    public function add(Request $request, \App\Models\Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $quantity = $validated['quantity'] ?? 1;

        $existing = CartItem::where('user_id', $request->user()->id)->where('product_id', $product->id)->first();
        $desiredQuantity = ($existing?->quantity ?? 0) + $quantity;
        $cappedQuantity = min($desiredQuantity, $product->stock_quantity, 99);

        abort_if($cappedQuantity < 1, 400, 'This item is out of stock.');

        if ($existing) {
            $existing->update(['quantity' => $cappedQuantity]);
        } else {
            CartItem::create([
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'quantity' => $cappedQuantity,
            ]);
        }

        return back()->with('status', 'Added to cart.');
    }

    public function updateQuantity(Request $request, CartItem $cartItem)
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $capped = min($validated['quantity'], $cartItem->product->stock_quantity);
        $cartItem->update(['quantity' => max(1, $capped)]);

        return back();
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('status', 'Removed from cart.');
    }

    /** Checkout page for the whole cart: shipping + delivery map + payment method, all in one order. */
    public function checkout(Request $request)
    {
        $items = $request->user()->cartItems()->with('product')->get();

        abort_if($items->isEmpty(), 400, 'Your cart is empty.');

        return view('cart.checkout', [
            'items' => $items,
            'total' => $items->sum(fn (CartItem $item) => $item->subtotal),
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Places the cart as ONE order holding every item (single shop — no
     * more splitting by seller). Stock is checked and reserved
     * immediately; the order starts as "pending" until the buyer
     * uploads a payment screenshot and staff confirms it against the
     * shop's bank.
     */
    public function placeOrder(Request $request)
    {
        $user = $request->user();
        $items = $user->cartItems()->with('product')->get();
        abort_if($items->isEmpty(), 400, 'Your cart is empty.');

        $validated = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ], [
            'shipping_phone.regex' => 'Please enter a valid phone number (digits, spaces, +, -, and () only).',
        ]);

        // Re-check stock right before committing — it may have changed since the item was added.
        foreach ($items as $item) {
            abort_if($item->quantity > $item->product->stock_quantity, 400, "Not enough stock left for \"{$item->product->title}\".");
        }

        $order = DB::transaction(function () use ($user, $items, $validated) {
            $order = Order::create([
                'buyer_id' => $user->id,
                'payment_method_id' => $validated['payment_method_id'],
                'total_price' => $items->sum(fn (CartItem $item) => $item->subtotal),
                'status' => Order::STATUS_PENDING,
                'shipping_name' => $validated['shipping_name'],
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'delivery_latitude' => $validated['delivery_latitude'] ?? null,
                'delivery_longitude' => $validated['delivery_longitude'] ?? null,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                ]);

                $item->product->decrementStock($item->quantity);
            }

            return $order;
        });

        $user->cartItems()->delete();

        return redirect()->route('orders.show', $order)->with('status', 'Order placed! Follow the payment instructions to complete it.');
    }
}
