<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $fillable = [
        'product_id',
        'version',
        'release_notes',
        'release_at',
    ];

    protected $casts = [
        'release_at' => 'datetime',
    ];
    /**
     * Get the release notes for the version.
     *
     * @return string
     */
    public function getReleaseNotesAttribute()
    {
        return $this->attributes['release_notes'];
    }

    public function setReleaseNotesAttribute($value)
    {
        $this->attributes['release_notes'] = $value;
    }

    // Accessor for releaseAt
    public function getReleaseAtAttribute()
    {
        return $this->attributes['release_at'];
    }
    // Mutator for releaseAt
    public function setReleaseAtAttribute($value)
    {
        $this->attributes['release_at'] = $value;
    }
    // リレーション: 1つのバージョンは1つの製品に属する
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
