<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    // レスポンスモデルの定義
    protected $fillable = [
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
     * レスポンスに基づくレビューを取得
     */
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * レスポンスしたユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
