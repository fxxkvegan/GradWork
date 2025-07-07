<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    // レスポンスモデルの定義
    protected $fillable = [
        'id',
        'review_id',
        'author_id',
        'body',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * レビューに紐づく製品を取得
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * レスポンスしたユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
