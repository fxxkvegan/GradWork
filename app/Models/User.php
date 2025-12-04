<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\Conversation;
use App\Models\Message;
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
        'display_name',
        'email',
        'password',
        'avatar_url',
        'header_url',
        'locale',
        'theme',
        'bio',
        'location',
        'website',
        'birthday',
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
        'birthday' => 'date',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'followed_id', 'follower_id');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'user_follows', 'follower_id', 'followed_id');
    }

    public function isFollowing(self $user): bool
    {
        return $this->following()->where('users.id', $user->id)->exists();
    }
}
