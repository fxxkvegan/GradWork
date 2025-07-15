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

    protected $appends = ['categoryIds'];

    // categoriesリレーションを隠す（categoryIdsアクセサで表示）
    protected $hidden = ['categories'];

    // リレーション: 1つの製品に複数のレビュー
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // リレーション: 製品と複数のカテゴリ（多対多）
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    // categoryIdsのアクセサ（Swagger仕様に合わせて）
    public function getCategoryIdsAttribute()
    {
        if ($this->relationLoaded('categories')) {
            return $this->categories->pluck('id')->map(function($id) {
                return (string) $id;
            })->toArray();
        }
        return $this->categories()->pluck('categories.id')->map(function($id) {
            return (string) $id;
        })->toArray();
    }

    // downloadCountのアクセサ（Swagger仕様に合わせて）
    public function getDownloadCountAttribute($value)
    {
        return $this->attributes['download_count'];
    }

    // 平均評価を取得するアクセサ（オプション）
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }
}
