<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Single-shop model: everyone is either a customer (buyer), staff
     * (day-to-day operations), or admin (full control, including
     * managing staff/admin accounts themselves). There is no "seller"
     * role anymore — this is one shop, not a multi-vendor marketplace.
     */
    public const ROLE_BUYER = 'buyer';
    public const ROLE_STAFF = 'staff';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ---------- Relationships ----------

    /** Orders this user has placed (as a buyer). */
    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    /** Chat messages this user sent, on either side of a conversation. */
    public function chatsSent()
    {
        return $this->hasMany(Chat::class, 'sender_id');
    }

    /** Products this user has favorited/saved (buyers use this). */
    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /** Items currently sitting in this user's cart. */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // ---------- Role helpers ----------

    public function isBuyer(): bool
    {
        return $this->role === self::ROLE_BUYER;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** True for staff OR admin — the two roles that run the shop's back office. */
    public function isBackOffice(): bool
    {
        return $this->isStaff() || $this->isAdmin();
    }

    /** Only admins may manage other staff/admin accounts — staff cannot. */
    public function canManageStaff(): bool
    {
        return $this->isAdmin();
    }
}
