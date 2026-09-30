<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-shop support chat: a thread is scoped to (buyer, product) —
 * there's no per-seller split anymore, since any staff/admin account
 * can see and reply to any buyer's conversation. sender_id still tracks
 * who actually typed each message (the buyer, or whichever staff member
 * answered).
 */
class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'product_id',
        'sender_id',
        'message',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
