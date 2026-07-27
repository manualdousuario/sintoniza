<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Subscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'feed_id',
        'url',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /** Creates the subscription, or restores it when soft-deleted. */
    public static function subscribe(int $userId, string $normalizedUrl, DateTimeInterface $at): void
    {
        self::upsertState($userId, $normalizedUrl, null, $at);
    }

    public static function unsubscribe(int $userId, string $normalizedUrl, DateTimeInterface $at): void
    {
        self::upsertState($userId, $normalizedUrl, $at, $at);
    }

    /**
     * Written as a single upsert on the unique (url, user_id) index so that
     * concurrent gPodder clients cannot race a select-then-write.
     */
    private static function upsertState(
        int $userId,
        string $normalizedUrl,
        ?DateTimeInterface $deletedAt,
        DateTimeInterface $at,
    ): void {
        DB::table('subscriptions')->upsert(
            [
                'user_id' => $userId,
                'url' => $normalizedUrl,
                'deleted_at' => $deletedAt,
                'created_at' => $at,
                'updated_at' => $at,
            ],
            ['url', 'user_id'],
            ['deleted_at', 'updated_at']
        );
    }
}
