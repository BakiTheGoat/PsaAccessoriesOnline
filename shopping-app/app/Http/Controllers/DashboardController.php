<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Buyers land here. Staff/admin never do — they're routed to
     * /admin straight from login, but this is a safe fallback in case
     * they land here some other way.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isBackOffice()) {
            return redirect()->route('admin.dashboard');
        }

        $orders = Order::with(['items.product'])
            ->where('buyer_id', $user->id)
            ->latest()
            ->get();

        $threads = Chat::with(['product'])
            ->where('buyer_id', $user->id)
            ->latest()
            ->get()
            ->unique('product_id');

        $favorites = $user->favorites()->with(['images', 'reviews'])->latest('favorites.created_at')->get();
        $favoriteIds = $favorites->pluck('id');

        return view('dashboard.buyer', compact('orders', 'threads', 'favorites', 'favoriteIds'));
    }
}
