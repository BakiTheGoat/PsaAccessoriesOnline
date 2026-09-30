<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Status flow matches the QR + manual-bank-check payment process:
     * PENDING          -> order placed, buyer hasn't paid/uploaded proof yet
     * PAYMENT_SUBMITTED -> buyer uploaded a payment screenshot, waiting on staff to check their bank
     * PAID             -> staff/admin confirmed the money actually arrived
     * COMPLETED        -> order fulfilled/delivered
     * CANCELLED        -> either side backed out (only before PAID)
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAYMENT_SUBMITTED = 'payment_submitted';
    public const STATUS_PAID = 'paid';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAYMENT_SUBMITTED,
        self::STATUS_PAID,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** Human-friendly labels, e.g. for the status tracker on the order page. */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Waiting for payment',
        self::STATUS_PAYMENT_SUBMITTED => 'Checking payment',
        self::STATUS_PAID => 'Paid',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'buyer_id',
        'payment_method_id',
        'total_price',
        'status',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'delivery_latitude',
        'delivery_longitude',
        'payment_screenshot',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /** The products (and quantities) that make up this order. */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** The single review left for this order, if any. */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getPaymentScreenshotUrlAttribute(): ?string
    {
        if (! $this->payment_screenshot) {
            return null;
        }

        return str_starts_with($this->payment_screenshot, 'http')
            ? $this->payment_screenshot
            : asset('storage/' . $this->payment_screenshot);
    }
}
