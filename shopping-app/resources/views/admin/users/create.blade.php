@extends('layouts.app')
@section('title', 'Add staff/admin account')

@section('content')
    <h1 class="text-2xl font-bold mb-6 mt-6">Add staff/admin account</h1>

    <form action="{{ route('admin.users.store') }}" method="POST"
          class="bg-white border rounded-lg p-6 max-w-lg space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required
                   class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password</label>
            <input type="password" name="password" required
                   class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required
                   class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Role</label>
            <select name="role" required class="w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="staff" @selected(old('role') === 'staff')>Staff (products, stock, orders, payments)</option>
                <option value="admin" @selected(old('role') === 'admin')>Admin (everything, incl. managing accounts)</option>
                <option value="buyer" @selected(old('role') === 'buyer')>Buyer (rarely needed here — customers self-register)</option>
            </select>
        </div>

        <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-md hover:bg-orange-700 font-semibold">
            Create account
        </button>
    </form>
@endsection
