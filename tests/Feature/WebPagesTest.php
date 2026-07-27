<?php

use App\Livewire\Dashboard\AddSubscription;
use App\Livewire\Dashboard\Index;
use App\Models\Episode;
use App\Models\EpisodeAction;
use App\Models\Feed;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

// ------------------------------------------------------------------ guests

it('shows the landing page to guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee(__('sintoniza.home.hero_title_1'), false);
});

it('shows the login page to guests', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee(__('sintoniza.general.login'))
        ->assertSee('name="login"', false)
        ->assertSee('name="password"', false);
});

it('shows the register page to guests', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('name="username"', false);
});

it('shows the forgot password pages to guests', function () {
    $this->get('/forget-password')
        ->assertOk()
        ->assertSee('name="email"', false);

    $this->get('/forget-password/reset?token=abc123&email=a%40b.c')
        ->assertOk()
        ->assertSee('name="new_password"', false)
        ->assertSee('value="abc123"', false);
});

// -------------------------------------------------------------- registration

it('registers the first user as admin via the register form', function () {
    $this->post('/register', [
        'username' => 'firstuser',
        'password' => 'secret-password',
        'email' => 'first@example.com',
    ])->assertRedirect('/login');

    $user = User::where('name', 'firstuser')->first();

    expect($user)->not->toBeNull()
        ->and($user->is_admin)->toBeTrue()
        ->and($user->email)->toBe('first@example.com');
});

// ---------------------------------------------------------- authenticated

it('redirects a logged-in user from home to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/dashboard');
});

it('serves the dashboard pages to an authenticated user', function (string $path) {
    $user = User::factory()->create();

    $this->actingAs($user)->get($path)->assertOk();
})->with([
    '/dashboard',
    '/dashboard/add',
    '/dashboard/profile',
    '/dashboard/profile/latest-updates',
    '/dashboard/profile/devices',
]);

it('redirects guests away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('lists recent episode actions on the latest updates page', function () {
    $user = User::factory()->create();
    $feed = Feed::create(['feed_url' => 'https://example.com/feed.xml', 'title' => 'My Podcast']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/feed.xml',
    ]);
    $episode = Episode::create([
        'feed_id' => $feed->id,
        'media_url' => 'https://example.com/ep1.mp3',
        'title' => 'Episode One',
    ]);
    EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'episode_id' => $episode->id,
        'url' => 'https://example.com/ep1.mp3',
        'changed_at' => now(),
        'action' => 'play',
        'data' => ['position' => 60],
    ]);

    $this->actingAs($user)
        ->get('/dashboard/profile/latest-updates')
        ->assertOk()
        ->assertSee('Episode One');
});

// ------------------------------------------------------------- subscriptions

it('shows a subscription page to its owner', function () {
    $user = User::factory()->create();
    $feed = Feed::create(['feed_url' => 'https://example.com/feed.xml', 'title' => 'My Podcast']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/feed.xml',
    ]);

    $this->actingAs($user)
        ->get("/subscription/{$subscription->id}")
        ->assertOk()
        ->assertSee('My Podcast');
});

it('shows an episode page to the subscription owner', function () {
    $user = User::factory()->create();
    $feed = Feed::create(['feed_url' => 'https://example.com/feed.xml', 'title' => 'My Podcast']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/feed.xml',
    ]);
    $episode = Episode::create([
        'feed_id' => $feed->id,
        'media_url' => 'https://example.com/ep1.mp3',
        'title' => 'Episode One',
        'duration' => 3600,
    ]);
    EpisodeAction::create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'episode_id' => $episode->id,
        'url' => 'https://example.com/ep1.mp3',
        'changed_at' => now(),
        'action' => 'play',
        'data' => ['position' => 120, 'total' => 3600],
    ]);

    $this->actingAs($user)
        ->get("/subscription/{$subscription->id}/episode/{$episode->id}")
        ->assertOk()
        ->assertSee('Episode One')
        ->assertSee('id="audio-player"', false)
        ->assertSee('data-start-pos="120"', false);
});

it('forbids access to another user\'s subscription', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $feed = Feed::create(['feed_url' => 'https://example.com/feed.xml']);
    $subscription = Subscription::create([
        'user_id' => $owner->id,
        'feed_id' => $feed->id,
        'url' => 'https://example.com/feed.xml',
    ]);
    $episode = Episode::create([
        'feed_id' => $feed->id,
        'media_url' => 'https://example.com/ep1.mp3',
    ]);

    $this->actingAs($intruder)
        ->get("/subscription/{$subscription->id}")
        ->assertNotFound();

    $this->actingAs($intruder)
        ->get("/subscription/{$subscription->id}/episode/{$episode->id}")
        ->assertNotFound();
});

it('returns 404 for an unknown subscription', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/subscription/9999')->assertNotFound();
});

// --------------------------------------------------------------- actions

it('subscribes to a feed url from the add page', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test(AddSubscription::class)
        ->set('url', 'https://Example.com/feed.xml?utm_source=spam')
        ->call('subscribe')
        ->assertRedirect(route('dashboard'));

    expect(Subscription::where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects an invalid feed url', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user)
        ->test(AddSubscription::class)
        ->set('url', 'not-a-url')
        ->call('subscribe');

    expect(Subscription::where('user_id', $user->id)->exists())->toBeFalse();
});

it('unsubscribes (soft deletes) from the dashboard', function () {
    $user = User::factory()->create();
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'url' => 'https://example.com/feed.xml',
    ]);

    Livewire\Livewire::actingAs($user)
        ->test(Index::class)
        ->call('unsubscribe', $subscription->id)
        ->assertRedirect(route('dashboard'));

    expect(Subscription::where('id', $subscription->id)->exists())->toBeFalse()
        ->and(Subscription::withTrashed()->where('id', $subscription->id)->exists())->toBeTrue();
});

it('does not let a user unsubscribe another user\'s subscription', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $subscription = Subscription::create([
        'user_id' => $owner->id,
        'url' => 'https://example.com/feed.xml',
    ]);

    Livewire\Livewire::actingAs($intruder)
        ->test(Index::class)
        ->call('unsubscribe', $subscription->id);

    expect(Subscription::where('id', $subscription->id)->exists())->toBeTrue();
});
