<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Episode;
use App\Models\EpisodeAction;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'admin',
            'email' => 'admin@sintoniza.test',
            'password' => 'password',
        ]);

        $user = User::factory()->create([
            'name' => 'demo',
            'email' => 'demo@sintoniza.test',
            'password' => 'password',
        ]);

        $feeds = Feed::factory()->count(6)->create([
            'last_fetched_at' => now()->subHours(2),
            'next_fetch_at' => now()->addDay(),
        ])->each(function (Feed $feed) {
            Episode::factory()->count(5)->create([
                'feed_id' => $feed->id,
                'published_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        });

        $feeds->each(function (Feed $feed) use ($user) {
            Subscription::create([
                'user_id' => $user->id,
                'feed_id' => $feed->id,
                'url' => $feed->feed_url,
            ]);
        });

        Subscription::create([
            'user_id' => $user->id,
            'feed_id' => $feeds->first()->id,
            'url' => 'https://example.com/removed-feed.xml',
            'deleted_at' => now()->subDay(),
        ]);

        $device = Device::create([
            'user_id' => $user->id,
            'identifier' => 'antennapod',
            'name' => 'AntennaPod em Pixel',
            'data' => ['type' => 'mobile', 'caption' => 'Pixel'],
        ]);

        $sub = Subscription::where('user_id', $user->id)->first();
        Episode::where('feed_id', $sub->feed_id)->limit(3)->get()->each(function (Episode $ep) use ($user, $sub, $device) {
            EpisodeAction::create([
                'user_id' => $user->id,
                'subscription_id' => $sub->id,
                'episode_id' => $ep->id,
                'device_id' => $device->id,
                'url' => $ep->media_url_normalized,
                'action' => 'play',
                'changed_at' => now()->subHours(2),
                'data' => ['position' => 120, 'total' => 3600, 'started' => 0, 'guid' => 'demo-guid'],
            ]);
        });
    }
}
