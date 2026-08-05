<?php

namespace App\Models;

use App\Support\Url;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_id',
        'media_url',
        'url',
        'image_url',
        'duration',
        'title',
        'description',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected function mediaUrl(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): array => [
                'media_url' => $value,
                'media_url_normalized' => $value === null ? null : Url::normalize($value),
            ],
        );
    }

    /** @return BelongsTo<Feed, $this> */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }

    /** @return HasMany<EpisodeAction, $this> */
    public function episodeActions(): HasMany
    {
        return $this->hasMany(EpisodeAction::class);
    }
}
