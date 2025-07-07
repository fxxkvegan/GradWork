<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rating',
        'download_count',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rating' => 'float',
        'download_count' => 'integer',
    ];

    // リレーション: 1つの製品に複数のレビュー
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // 平均評価を取得するアクセサ（オプション）
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating');
    }
}
