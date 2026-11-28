<?php

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function dropFeedTablesMigration(): object
{
    return require database_path('migrations/2026_11_28_000000_drop_feed_tables.php');
}

it('leaves a schema without feed tables or feed columns', function () {
    expect(Schema::hasTable('feeds'))->toBeFalse()
        ->and(Schema::hasTable('feed_aliases'))->toBeFalse()
        ->and(Schema::hasTable('episodes'))->toBeFalse()
        ->and(Schema::hasColumn('subscriptions', 'feed_id'))->toBeFalse()
        ->and(Schema::hasColumn('episode_actions', 'episode_id'))->toBeFalse();
});

it('drops v3 feed data and keeps the synced data', function () {
    $migration = dropFeedTablesMigration();

    // Rebuild the v3 shape on top of the current schema.
    $migration->down();
    Schema::create('feeds', function (Blueprint $table) {
        $table->integer('id', true);
        $table->string('feed_url');
    });
    Schema::create('episodes', function (Blueprint $table) {
        $table->integer('id', true);
        $table->integer('feed_id');
    });

    $user = User::factory()->create();
    $feedId = DB::table('feeds')->insertGetId(['feed_url' => 'https://example.com/feed.xml']);
    DB::table('episodes')->insert(['feed_id' => $feedId]);
    $subscription = Subscription::create(['user_id' => $user->id, 'url' => 'https://example.com/feed.xml']);
    DB::table('subscriptions')->where('id', $subscription->id)->update(['feed_id' => $feedId]);

    $migration->up();
    $migration->up(); // idempotent

    expect(Schema::hasTable('feeds'))->toBeFalse()
        ->and(Schema::hasTable('episodes'))->toBeFalse()
        ->and(Schema::hasColumn('subscriptions', 'feed_id'))->toBeFalse()
        ->and(Subscription::find($subscription->id)->url)->toBe('https://example.com/feed.xml');
});
