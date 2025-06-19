<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'id',
        'name',
        'description',
        'raiting',
        'download_count',
        'created_at',
        'updated_at',
        // '追加したい分書く',
    ];
}
