<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStatus extends Model
{
    protected $fillable = [
        'status',
        'message',
        'product_id',
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

    const STATUS_ONLINE = 'online';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_DEPRECATED = 'deprecated';
    /**
     * Get the list of valid status values.
     *
     * @return array
     */
    public static function getStatusValues(){
        return [
            self::STATUS_ONLINE,
            self::STATUS_MAINTENANCE,
            self::STATUS_DEPRECATED,
        ];
    }
    
    //1つの製品ステータスは1つの製品に属する
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
