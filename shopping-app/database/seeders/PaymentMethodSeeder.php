<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Seeds "Cash on delivery" so checkout works immediately without any
     * setup. QR-based methods (ABA Pay, ACLEDA, Wing, etc.) need a real
     * QR image uploaded through the admin panel, so they aren't seeded
     * here — add them at /admin/payment-methods once logged in as admin.
     */
    public function run(): void
    {
        PaymentMethod::create([
            'name' => 'Cash on delivery / pickup',
            'qr_image_path' => null,
            'instructions' => 'Pay in cash when your order arrives, or when you pick it up.',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
