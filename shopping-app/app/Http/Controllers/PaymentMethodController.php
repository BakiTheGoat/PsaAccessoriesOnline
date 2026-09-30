<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Staff/admin manage the shop's payment options here — each one
 * optionally has a QR code image (ABA Pay, ACLEDA, Wing, etc.) that
 * buyers scan at checkout. "Cash on delivery" is just an entry with no
 * QR image.
 */
class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('sort_order')->get();

        return view('admin.payment_methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('admin.payment_methods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'qr_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $qrPath = null;
        if ($request->hasFile('qr_image')) {
            $qrPath = $request->file('qr_image')->store('payment-qr', 'public');
        }

        PaymentMethod::create([
            'name' => $validated['name'],
            'instructions' => $validated['instructions'] ?? null,
            'qr_image_path' => $qrPath,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => PaymentMethod::max('sort_order') + 1,
        ]);

        return redirect()->route('admin.paymentMethods.index')->with('status', 'Payment method added.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.payment_methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'qr_image' => ['nullable', 'image', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('qr_image')) {
            if ($paymentMethod->qr_image_path) {
                Storage::disk('public')->delete($paymentMethod->qr_image_path);
            }
            $paymentMethod->qr_image_path = $request->file('qr_image')->store('payment-qr', 'public');
        }

        $paymentMethod->update([
            'name' => $validated['name'],
            'instructions' => $validated['instructions'] ?? null,
            'qr_image_path' => $paymentMethod->qr_image_path,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.paymentMethods.index')->with('status', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->qr_image_path) {
            Storage::disk('public')->delete($paymentMethod->qr_image_path);
        }

        $paymentMethod->delete();

        return redirect()->route('admin.paymentMethods.index')->with('status', 'Payment method removed.');
    }
}
