<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\EpisodeAction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'admin',
            'email' => 'admin@sintoniza.test',
            'password' => 'password',
        ]);

        $user = User::factory()->create([
            'name' => 'demo',
            'email' => 'demo@sintoniza.test',
            'password' => 'password',
        ]);

        foreach (range(1, 6) as $n) {
            Subscription::create([
                'user_id' => $user->id,
                'url' => "https://example.com/podcast-{$n}.xml",
            ]);
        }

        Subscription::create([
            'user_id' => $user->id,
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

        foreach (range(1, 3) as $n) {
            EpisodeAction::create([
                'user_id' => $user->id,
                'subscription_id' => $sub->id,
                'device_id' => $device->id,
                'url' => "https://example.com/podcast-1/episode-{$n}.mp3",
                'action' => 'play',
                'changed_at' => now()->subHours(2),
                'data' => ['position' => 120, 'total' => 3600, 'started' => 0, 'guid' => "demo-guid-{$n}"],
            ]);
        }
    }
}
