<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed payment options — each one optionally carries a QR code
 * image (for ABA Pay / ACLEDA / Wing style bank transfers) that gets
 * shown to the buyer at checkout. "Cash on delivery" is just an entry
 * here with no QR image.
 */
class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'qr_image_path', 'instructions', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getQrUrlAttribute(): ?string
    {
        if (! $this->qr_image_path) {
            return null;
        }

        return str_starts_with($this->qr_image_path, 'http')
            ? $this->qr_image_path
            : asset('storage/' . $this->qr_image_path);
    }
}
