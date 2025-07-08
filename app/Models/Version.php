<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
        'product_id',
        'version',
        'releaseNote',
        'releaseAt',
    ];

    protected $casts = [
        'releaseAt' => 'datetime',
    ];

    // リレーション: 1つのバージョンは1つの製品に属する
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
