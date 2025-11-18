<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
