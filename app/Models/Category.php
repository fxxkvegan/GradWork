<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use function json_decode;
use function json_last_error;
use function is_array;
use function is_string;
use function array_filter;
use function array_values;

class Category extends Model
{
    protected $fillable = [
        'name',
        'image', 
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * リレーション: カテゴリと複数の製品（多対多）
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories');
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
