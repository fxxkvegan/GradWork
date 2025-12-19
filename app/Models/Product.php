<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProductFile;

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
        'access_count',
        'google_play_url',
        'app_store_url',
        'web_app_url',
        'image_url',
        'user_id',
        'file_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rating' => 'float',
        'access_count' => 'integer',
        'image_url' => 'string',
        'file_status' => 'string',
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

    public function files()
    {
        return $this->hasMany(ProductFile::class);
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

    /**
     * アクセス回数のアクセサ
     */
    public function getAccessCountAttribute($value)
    {
        return $this->attributes['access_count'] ?? 0;
    }

    public function getFileStatusAttribute($value): string
    {
        return $value ?: 'none';
    }

    /**
     * 外部リンクが設定されているかどうかを判定
     */
    public function hasExternalLinks(): bool
    {
        return !empty($this->google_play_url)
            || !empty($this->app_store_url)
            || !empty($this->web_app_url);
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
