<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFile extends Model
{
    protected $fillable = [
        'product_id',
        'path',
        'type',
        'size',
        'mime',
        'is_previewable',
        'preview_text',
    ];

    protected $casts = [
        'is_previewable' => 'boolean',
        'size' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
