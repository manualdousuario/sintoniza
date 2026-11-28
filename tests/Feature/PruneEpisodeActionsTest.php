<?php

use App\Models\Device;
use App\Models\EpisodeAction;
use App\Models\Subscription;
use App\Models\User;

function pruneAction(User $user, Subscription $subscription, ?Device $device, string $url, string $action, string $at): EpisodeAction
{
    return EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'device_id' => $device?->id,
        'url' => $url,
        'action' => $action,
        'changed_at' => $at,
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->subscription = Subscription::create(['user_id' => $this->user->id, 'url' => 'https://example.com/feed.xml']);
    $this->phone = Device::create(['user_id' => $this->user->id, 'identifier' => 'phone']);
    $this->laptop = Device::create(['user_id' => $this->user->id, 'identifier' => 'laptop']);
});

it('keeps only the latest action per episode and device', function () {
    $old = pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'download', '2026-01-01 10:00:00');
    $mid = pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'play', '2026-01-02 10:00:00');
    $latest = pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'play', '2026-01-03 10:00:00');
    $otherEpisode = pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/2.mp3', 'play', '2026-01-01 10:00:00');

    $this->artisan('sintoniza:prune-episode-actions')
        ->expectsOutputToContain('Done: 2 episode action(s) removed')
        ->assertExitCode(0);

    expect(EpisodeAction::pluck('id')->sort()->values()->all())
        ->toBe(collect([$latest->id, $otherEpisode->id])->sort()->values()->all())
        ->and(EpisodeAction::find($old->id))->toBeNull()
        ->and(EpisodeAction::find($mid->id))->toBeNull();
});

it('keeps each device and actions without a device apart', function () {
    pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'play', '2026-01-01 10:00:00');
    pruneAction($this->user, $this->subscription, $this->laptop, 'https://cdn.example.com/1.mp3', 'play', '2026-01-02 10:00:00');
    pruneAction($this->user, $this->subscription, null, 'https://cdn.example.com/1.mp3', 'play', '2026-01-03 10:00:00');
    pruneAction($this->user, $this->subscription, null, 'https://cdn.example.com/1.mp3', 'play', '2026-01-04 10:00:00');

    $this->artisan('sintoniza:prune-episode-actions')->assertExitCode(0);

    expect(EpisodeAction::count())->toBe(3)
        ->and(EpisodeAction::whereNull('device_id')->value('changed_at')->toDateTimeString())->toBe('2026-01-04 10:00:00');
});

it('breaks timestamp ties by keeping the newest row', function () {
    pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'download', '2026-01-01 10:00:00');
    $newer = pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'play', '2026-01-01 10:00:00');

    $this->artisan('sintoniza:prune-episode-actions')->assertExitCode(0);

    expect(EpisodeAction::pluck('id')->all())->toBe([$newer->id]);
});

it('does not mix users', function () {
    $other = User::factory()->create();
    $otherSubscription = Subscription::create(['user_id' => $other->id, 'url' => 'https://example.com/feed.xml']);

    pruneAction($this->user, $this->subscription, null, 'https://cdn.example.com/1.mp3', 'play', '2026-01-01 10:00:00');
    pruneAction($other, $otherSubscription, null, 'https://cdn.example.com/1.mp3', 'play', '2026-01-02 10:00:00');

    $this->artisan('sintoniza:prune-episode-actions')->assertExitCode(0);

    expect(EpisodeAction::count())->toBe(2);
});

it('only counts in dry-run mode', function () {
    pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'play', '2026-01-01 10:00:00');
    pruneAction($this->user, $this->subscription, $this->phone, 'https://cdn.example.com/1.mp3', 'play', '2026-01-02 10:00:00');

    $this->artisan('sintoniza:prune-episode-actions --dry-run')
        ->expectsOutputToContain('Done: 1 episode action(s) would be removed')
        ->assertExitCode(0);

    expect(EpisodeAction::count())->toBe(2);
});

it('is scheduled daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('sintoniza:prune-episode-actions')
        ->assertExitCode(0);
});
