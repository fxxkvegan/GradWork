<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function json_decode;
use function json_last_error;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rating',
        'download_count',
        'image_url',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rating' => 'float',
        'download_count' => 'integer',
        'image_url' => 'array',
    ];

    protected $appends = ['categoryIds'];

    // categoriesリレーションを隠す（categoryIdsアクセサで表示）
    protected $hidden = ['categories'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    public static function decodeImageUrls($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values(array_filter($value, static function ($url) {
                return is_string($url) && $url !== '';
            }));
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, static function ($url) {
                    return is_string($url) && $url !== '';
                }));
            }

            if (is_string($decoded) && $decoded !== '') {
                return [$decoded];
            }
        }

        return is_string($value) && $value !== '' ? [$value] : [];
    }
}
