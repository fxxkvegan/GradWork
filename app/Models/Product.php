<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'image',
        'ranking',
        'category_id',
        // '追加したい分書く',
    ];
}
