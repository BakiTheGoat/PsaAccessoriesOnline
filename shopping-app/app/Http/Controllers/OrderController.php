<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * "Buy Now" checkout page — a single-product shortcut that skips the
     * cart. Same shipping/map/payment-method form as the cart checkout.
     */
    public function checkout(Request $request, Product $product)
    {
        abort_if(! $product->in_stock, 400, 'This item is currently out of stock.');

        $product->load('images');

        return view('orders.checkout', [
            'product' => $product,
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /** Submits the "Buy Now" checkout form and creates a one-item pending order. */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'delivery_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
        ], [
            'shipping_phone.regex' => 'Please enter a valid phone number (digits, spaces, +, -, and () only).',
        ]);

        abort_if($validated['quantity'] > $product->stock_quantity, 400, 'Not enough stock left.');

        $order = DB::transaction(function () use ($request, $product, $validated) {
            $order = Order::create([
                'buyer_id' => $request->user()->id,
                'payment_method_id' => $validated['payment_method_id'],
                'total_price' => $product->price * $validated['quantity'],
                'status' => Order::STATUS_PENDING,
                'shipping_name' => $validated['shipping_name'],
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'delivery_latitude' => $validated['delivery_latitude'] ?? null,
                'delivery_longitude' => $validated['delivery_longitude'] ?? null,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $product->price,
            ]);

            $product->decrementStock($validated['quantity']);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Order placed! Follow the payment instructions to complete it.');
    }

    /** Order receipt page — shows items, status tracker, and (while pending) the QR + upload form. */
    public function show(Order $order)
    {
        $this->authorizeView($order);

        $order->load(['items.product.images', 'buyer', 'paymentMethod', 'review']);

        return view('orders.show', compact('order'));
    }

    /** Buyer uploads a screenshot of their bank/wallet payment as proof. */
    public function uploadScreenshot(Request $request, Order $order)
    {
        abort_if($order->buyer_id !== $request->user()->id, 403);
        abort_if($order->status !== Order::STATUS_PENDING, 400, 'This order is past the payment step.');

        $validated = $request->validate([
            'payment_screenshot' => ['required', 'image', 'max:4096'],
        ]);

        if ($order->payment_screenshot) {
            Storage::disk('public')->delete($order->payment_screenshot);
        }

        $path = $request->file('payment_screenshot')->store('payment-proofs', 'public');

        $order->update([
            'payment_screenshot' => $path,
            'status' => Order::STATUS_PAYMENT_SUBMITTED,
        ]);

        return back()->with('status', 'Thanks! We\'re checking your payment now.');
    }

    /**
     * Status changes. Approving or rejecting a payment (moving to/from
     * "paid") is admin-only — that's a financial check against the
     * shop's actual bank account, not a routine operational task, so
     * staff deliberately can't do it, even by hitting this route
     * directly. Staff can still mark an order completed or cancel it.
     * A buyer may only cancel their own order, and only while it's
     * still pending (before they've even submitted a payment
     * screenshot).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::STATUSES)],
        ]);

        $user = $request->user();
        $newStatus = $validated['status'];
        $isPaymentDecision = $newStatus === Order::STATUS_PAID
            || ($order->status === Order::STATUS_PAYMENT_SUBMITTED && $newStatus === Order::STATUS_PENDING);

        if ($isPaymentDecision) {
            abort_unless($user->isAdmin(), 403, 'Only an admin can approve or reject a payment.');
        } elseif ($user->isBackOffice()) {
            // Staff/admin can still mark an order completed or cancel it.
        } elseif ($user->id === $order->buyer_id && $newStatus === Order::STATUS_CANCELLED) {
            abort_if($order->status !== Order::STATUS_PENDING, 403, 'This order can no longer be cancelled.');
        } else {
            abort(403, 'You cannot update this order.');
        }

        // Cancelling gives the stock back.
        if ($newStatus === Order::STATUS_CANCELLED && $order->status !== Order::STATUS_CANCELLED) {
            foreach ($order->items as $item) {
                $item->product->restoreStock($item->quantity);
            }
        }

        $order->update(['status' => $newStatus]);

        return back()->with('status', 'Order updated.');
    }

    /** Only the buyer themself, or any staff/admin account, may view an order. */
    private function authorizeView(Order $order): void
    {
        $user = request()->user();

        abort_if($user->id !== $order->buyer_id && ! $user->isBackOffice(), 403);
    }
}
