<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The big structural pivot: from a multi-seller marketplace to a single
 * shop. This migration:
 *  - adds stock_quantity to products, renames products.user_id to
 *    created_by (it's a record-keeping link now, not "ownership")
 *  - creates payment_methods (admin-managed QR codes: ABA Pay, ACLEDA,
 *    Wing, Cash on delivery, etc.)
 *  - creates order_items (an order can now hold many products, since
 *    there's only one shop — no more splitting a cart into one order
 *    per seller)
 *  - reshapes orders: drops the single product_id/quantity/seller_id
 *    columns, adds payment_screenshot + delivery map fields, and a new
 *    status list matching the QR-and-screenshot payment flow
 *  - drops the old fake-card fields (card_brand, card_last4,
 *    saved_cards) since payment is now QR + manual bank verification,
 *    not a card form
 *  - removes seller_id from chats (support chat is now buyer <-> "the
 *    shop", not buyer <-> a specific seller)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Products: add stock, rename ownership column ----------
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(0)->after('category');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('user_id', 'created_by');
        });

        // ---------- Payment methods (admin-managed QR codes) ----------
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "ABA Pay (KHQR)", "Cash on delivery"
            $table->string('qr_image_path')->nullable(); // null for cash
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // ---------- Order items (many products per order now) ----------
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2); // price at time of order, in case it changes later
            $table->timestamps();
        });

        // ---------- Orders: drop old single-product/seller fields, add new ones ----------
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['seller_id']);
            $table->dropColumn(['product_id', 'quantity', 'seller_id', 'payment_method', 'card_brand', 'card_last4']);

            $table->foreignId('payment_method_id')->nullable()->after('buyer_id')->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_screenshot')->nullable()->after('shipping_address');
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('payment_screenshot');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
        });

        // ---------- Chats: no more per-seller scoping (single shared shop inbox) ----------
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
        });

        // ---------- Saved cards no longer used (payment is QR + screenshot now) ----------
        Schema::dropIfExists('saved_cards');
    }

    public function down(): void
    {
        // This migration is intentionally one-way — the pivot is a
        // deliberate redesign, not something to casually roll back.
        // If you truly need to reverse it, restore from a backup taken
        // before running `php artisan migrate` for this file.
    }
};
