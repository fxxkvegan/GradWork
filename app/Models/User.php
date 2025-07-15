<?php

namespace App\Models;

use App\Http\Controllers\Api\AuthController;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\sanctum\HasApiTokens;
class User extends Authenticatable
{
    use Notifiable, HasApiTokens;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'locale',
        'theme',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
