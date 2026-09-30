<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const CONDITION_NEW = 'new';
    public const CONDITION_USED = 'used';

    /** Fixed category list used for the browse-by-category UI. */
    public const CATEGORIES = [
        'Electronics',
        'Fashion',
        'Home & Living',
        'Sports & Outdoors',
        'Books & Study',
        'Vehicles',
        'Others',
    ];

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'price',
        'condition',
        'category',
        'stock_quantity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    // ---------- Relationships ----------

    /** The staff/admin account that originally added this listing (just a record-keeping link, not an "owner" — this is a single shop). */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** All uploaded images for this product. */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /** Reviews left by buyers who completed an order for this product. */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** Seller-entered spec rows (Material -> Cotton, etc), shown as a details table. */
    public function specs()
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    /** Users who have favorited/saved this product. */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /** Every order line that has ever included this product. */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getAverageRatingAttribute(): ?float
    {
        return $this->reviews->isEmpty() ? null : round($this->reviews->avg('rating'), 1);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews->count();
    }

    /** True when there's at least one unit available to buy. */
    public function getInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    // ---------- Stock helpers ----------

    /** Reserve stock for a new order (called when an order is placed). */
    public function decrementStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
    }

    /** Give stock back (called when an order is cancelled). */
    public function restoreStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
    }

    // ---------- Query scopes (used by the search/filter form) ----------

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeCondition($query, ?string $condition)
    {
        if (! $condition) {
            return $query;
        }

        return $query->where('condition', $condition);
    }

    public function scopeCategory($query, ?string $category)
    {
        if (! $category) {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopePriceBetween($query, ?float $min, ?float $max)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }

        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        return $query;
    }

    public function scopeInStockOnly($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }
}
