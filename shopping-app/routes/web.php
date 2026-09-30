<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Single-shop model
|--------------------------------------------------------------------------
| Two back-office roles now instead of "seller": 'staff' (day-to-day —
| products, stock, orders, approving payments) and 'admin' (everything
| staff can do, PLUS managing staff/admin accounts themselves).
|
| ORDERING NOTE: '/products/{product}' is a wildcard, so any fixed route
| under /products/ (like /products/create) MUST be registered above it,
| or Laravel will try to treat "create" as a product ID and 404.
*/

// -------------------- Public --------------------
Route::get('/', [ProductController::class, 'home'])->name('home');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// -------------------- Staff/Admin only (must come before /products/{product}) --------------------
Route::middleware(['auth', 'role:staff,admin'])->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// -------------------- Public (wildcard — must stay after /products/create) --------------------
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// -------------------- Guest only --------------------
Route::middleware('guest')->group(function () {
    // Self-registration is buyer-only. Staff/admin accounts are created
    // from the admin panel (or seeded) — never self-registered.
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// -------------------- Any authenticated user --------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Support chat: scoped to (buyer, product) — any staff/admin can view/reply.
    Route::get('/chat/{product}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{product}', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{product}/messages', [ChatController::class, 'messages'])->name('chat.messages');

    Route::post('/products/{product}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/checkout', [CartController::class, 'placeOrder'])->name('cart.placeOrder');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateQuantity'])->name('cart.updateQuantity');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/payment-screenshot', [OrderController::class, 'uploadScreenshot'])->name('orders.uploadScreenshot');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::post('/orders/{order}/review', [ReviewController::class, 'store'])->name('reviews.store');
});

// -------------------- Staff/Admin: back office --------------------
Route::middleware(['auth', 'role:staff,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/orders', [AdminController::class, 'orders'])->name('orders.index');

    Route::get('/products', [AdminController::class, 'products'])->name('products.index');
    Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');

    Route::get('/payment-methods', [PaymentMethodController::class, 'index'])->name('paymentMethods.index');
    Route::get('/payment-methods/create', [PaymentMethodController::class, 'create'])->name('paymentMethods.create');
    Route::post('/payment-methods', [PaymentMethodController::class, 'store'])->name('paymentMethods.store');
    Route::get('/payment-methods/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])->name('paymentMethods.edit');
    Route::put('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('paymentMethods.update');
    Route::delete('/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('paymentMethods.destroy');
});

// -------------------- Admin only: managing staff/admin accounts --------------------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
});
