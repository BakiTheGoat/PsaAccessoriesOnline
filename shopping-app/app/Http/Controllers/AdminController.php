<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /** Back-office overview with headline counts — visible to staff and admin alike. */
    public function dashboard()
    {
        $stats = [
            'users' => User::where('role', User::ROLE_BUYER)->count(),
            'staff' => User::where('role', User::ROLE_STAFF)->count(),
            'products' => Product::count(),
            'out_of_stock' => Product::where('stock_quantity', 0)->count(),
            'orders' => Order::count(),
            'payments_to_check' => Order::where('status', Order::STATUS_PAYMENT_SUBMITTED)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // ---------- Orders (staff + admin: this is where payments get approved) ----------

    public function orders()
    {
        $orders = Order::with(['buyer', 'items.product', 'paymentMethod'])
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    // ---------- Products (staff + admin) ----------

    public function products()
    {
        $products = Product::with(['images'])->latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function destroyProduct(Product $product)
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Product removed.');
    }

    // ---------- Users (admin ONLY — staff cannot manage accounts, incl. their own role) ----------

    public function users()
    {
        $users = User::latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    /** Admin creates a staff (or admin) account directly — there's no self-registration for these roles. */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'in:buyer,staff,admin'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Account created.');
    }

    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:buyer,staff,admin'],
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroyUser(User $user)
    {
        abort_if($user->id === request()->user()->id, 403, "You can't delete your own account.");

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
