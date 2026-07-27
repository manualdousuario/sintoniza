<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_url',
        'image_url',
        'url',
        'language',
        'title',
        'description',
        'published_at',
        'last_fetched_at',
        'next_fetch_at',
        'etag',
        'last_modified',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'last_fetched_at' => 'datetime',
            'next_fetch_at' => 'datetime',
        ];
    }

    /** @return HasMany<Episode, $this> */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
