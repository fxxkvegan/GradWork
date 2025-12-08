<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'product_id',
        'author_id',
        'title',
        'body',
        'helpful_count',
        'rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns the review.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(ReviewNotificationRead::class);
    }

    /**
     * Ensure rating is persisted as an integer (value * 2) while exposing decimal values to callers.
     */
    protected function rating(): Attribute
    {
        return Attribute::make(
            get: static fn (?int $value) => $value === null ? null : round($value / 2, 1),
            set: static fn (?float $value) => $value === null
                ? null
                : (int) round($value * 2),
        );
    }
}
