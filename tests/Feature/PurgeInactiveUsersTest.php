<?php

use App\Models\Device;
use App\Models\EpisodeAction;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    config(['sintoniza.inactive_user_months' => 6]);
});

function userWithData(array $attributes = []): User
{
    $user = User::factory()->create($attributes);

    $feed = Feed::create(['feed_url' => 'https://feeds.example.com/'.$user->name.'.xml']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => $feed->feed_url,
    ]);
    $device = Device::create(['user_id' => $user->id, 'identifier' => 'phone']);

    EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'device_id' => $device->id,
        'url' => 'https://cdn.example.com/1.mp3',
        'changed_at' => now()->subMonths(7),
        'action' => 'play',
    ]);

    return $user;
}

it('removes users inactive for six months with all their records', function () {
    $user = userWithData(['last_activity_at' => now()->subMonths(7)]);
    DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'x', 'created_at' => now()]);

    $this->artisan('sintoniza:purge-inactive-users')
        ->expectsOutputToContain('Done: 1 user(s) removed')
        ->assertExitCode(0);

    expect(User::find($user->id))->toBeNull()
        ->and(Subscription::withTrashed()->where('user_id', $user->id)->count())->toBe(0)
        ->and(Device::where('user_id', $user->id)->count())->toBe(0)
        ->and(EpisodeAction::where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('password_reset_tokens')->where('email', $user->email)->count())->toBe(0)
        // Shared data stays.
        ->and(Feed::count())->toBe(1);
});

it('falls back to created_at when the user was never seen', function () {
    $user = User::factory()->create(['last_activity_at' => null]);
    $user->forceFill(['created_at' => now()->subMonths(8)])->save();

    $this->artisan('sintoniza:purge-inactive-users')->assertExitCode(0);

    expect(User::find($user->id))->toBeNull();
});

it('keeps active users and admins', function () {
    $active = userWithData(['last_activity_at' => now()->subMonth()]);
    $admin = userWithData(['is_admin' => true, 'last_activity_at' => now()->subYears(2)]);

    $this->artisan('sintoniza:purge-inactive-users')
        ->expectsOutputToContain('Done: 0 user(s) removed')
        ->assertExitCode(0);

    expect(User::find($active->id))->not->toBeNull()
        ->and(User::find($admin->id))->not->toBeNull()
        ->and(EpisodeAction::count())->toBe(2);
});

it('does not delete anything on dry run', function () {
    $user = userWithData(['last_activity_at' => now()->subMonths(7)]);

    $this->artisan('sintoniza:purge-inactive-users', ['--dry-run' => true])
        ->expectsOutputToContain($user->name)
        ->expectsOutputToContain('Done: 1 user(s) would be removed')
        ->assertExitCode(0);

    expect(User::find($user->id))->not->toBeNull();
});

it('never deletes by default', function () {
    // INACTIVE_USER_MONTHS is not set in the test environment.
    config(['sintoniza.inactive_user_months' => (require config_path('sintoniza.php'))['inactive_user_months']]);
    $user = User::factory()->create(['last_activity_at' => now()->subYears(3)]);

    expect(config('sintoniza.inactive_user_months'))->toBe(0);

    $this->artisan('sintoniza:purge-inactive-users')
        ->expectsOutputToContain('disabled')
        ->assertExitCode(0);

    expect(User::find($user->id))->not->toBeNull();
});

it('respects the configured number of months', function () {
    config(['sintoniza.inactive_user_months' => 12]);
    $recent = User::factory()->create(['last_activity_at' => now()->subMonths(7)]);
    $old = User::factory()->create(['last_activity_at' => now()->subMonths(13)]);

    $this->artisan('sintoniza:purge-inactive-users')
        ->expectsOutputToContain('Done: 1 user(s) removed')
        ->assertExitCode(0);

    expect(User::find($recent->id))->not->toBeNull()
        ->and(User::find($old->id))->toBeNull();
});

it('is disabled when the threshold is zero', function () {
    config(['sintoniza.inactive_user_months' => 0]);
    $user = User::factory()->create(['last_activity_at' => now()->subYears(3)]);

    $this->artisan('sintoniza:purge-inactive-users')
        ->expectsOutputToContain('disabled')
        ->assertExitCode(0);

    expect(User::find($user->id))->not->toBeNull();
});

it('is scheduled daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('sintoniza:purge-inactive-users')
        ->assertExitCode(0);
});

// -------------------------------------------------------- activity tracking

it('tracks activity on authenticated gpodder requests', function () {
    $user = User::factory()->create(['name' => 'testuser', 'password' => 'secret-password', 'last_activity_at' => null]);

    $this->call('GET', '/api/2/devices/testuser.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200);

    expect($user->fresh()->last_activity_at)->not->toBeNull();
});

it('tracks activity on the dashboard at most once an hour', function () {
    $seen = now()->subMinutes(10)->startOfSecond();
    $user = User::factory()->create(['last_activity_at' => $seen]);

    $this->actingAs($user)->get('/dashboard')->assertOk();
    expect($user->fresh()->last_activity_at->equalTo($seen))->toBeTrue();

    $user->forceFill(['last_activity_at' => now()->subHours(2)])->save();

    $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    expect($user->fresh()->last_activity_at->gt(now()->subMinute()))->toBeTrue();
});

it('does not bump updated_at when tracking activity', function () {
    $user = User::factory()->create(['last_activity_at' => null]);
    $user->forceFill(['updated_at' => now()->subYear()])->save();
    $updatedAt = $user->fresh()->updated_at;

    $user->fresh()->touchActivity();

    expect($user->fresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});

it('backfills last_activity_at from the user history', function () {
    $this->freezeSecond();
    $user = userWithData(['last_activity_at' => null]);
    $user->forceFill(['created_at' => now()->subYears(2), 'updated_at' => now()->subYears(2)])->save();
    Subscription::where('user_id', $user->id)->update(['updated_at' => now()->subYears(2)]);
    Device::where('user_id', $user->id)->update(['updated_at' => now()->subYears(2)]);
    EpisodeAction::where('user_id', $user->id)->update(['changed_at' => now()->subMonths(3)->startOfSecond()]);

    $migration = require database_path('migrations/2026_11_26_000000_add_last_activity_at_to_users_table.php');
    $migration->up();

    expect($user->fresh()->last_activity_at->equalTo(now()->subMonths(3)->startOfSecond()))->toBeTrue();
});
