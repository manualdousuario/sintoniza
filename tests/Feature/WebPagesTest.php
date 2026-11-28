<?php

use App\Models\Device;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// ------------------------------------------------------------------ guests

it('shows the landing page to guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee(__('sintoniza.home.title'))
        ->assertSee('https://github.com/manualdousuario/sintoniza', false)
        ->assertDontSee('<script', false);
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

it('flags the invalid field when registration fails', function () {
    $this->from('/register')
        ->post('/register', [
            'username' => 'ok_name',
            'password' => 'short',
            'email' => 'first@example.com',
        ])
        ->assertRedirect('/register')
        ->assertSessionHasErrors('password');
});

// ---------------------------------------------------------- authenticated

it('redirects a logged-in user from home to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect('/dashboard');
});

it('redirects guests away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('shows connection details, devices and subscriptions on the account page', function () {
    $user = User::factory()->create(['name' => 'listener']);
    Device::create([
        'user_id' => $user->id,
        'identifier' => 'antennapod',
        'name' => 'Pixel',
        'data' => ['type' => 'mobile'],
    ]);
    Subscription::create(['user_id' => $user->id, 'url' => 'https://example.com/active.xml']);
    Subscription::create(['user_id' => $user->id, 'url' => 'https://example.com/gone.xml'])->delete();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('listener__'.$user->gpodderToken())
        ->assertSee('Pixel')
        ->assertSee('https://example.com/active.xml')
        ->assertDontSee('https://example.com/gone.xml')
        ->assertSee('/subscriptions/listener.opml', false)
        ->assertDontSee('<script', false);
});

it('shows the Nextcloud login confirmation', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard?oktoken')
        ->assertOk()
        ->assertSee(__('sintoniza.messages.login_success'));
});

it('redirects the old v3 pages to the account page', function (string $path) {
    $this->actingAs(User::factory()->create())
        ->get($path)
        ->assertRedirect('/dashboard')
        ->assertStatus(301);
})->with(['/dashboard/profile', '/dashboard/devices', '/dashboard/profile/devices']);

it('no longer serves the episode and podcast pages', function (string $path) {
    $this->actingAs(User::factory()->create())->get($path)->assertNotFound();
})->with(['/dashboard/add', '/dashboard/profile/latest-updates', '/subscription/1', '/subscription/1/episode/1']);

it('unsubscribes (soft deletes) from the account page', function () {
    $user = User::factory()->create();
    $subscription = Subscription::create(['user_id' => $user->id, 'url' => 'https://example.com/feed.xml']);

    $this->actingAs($user)
        ->delete("/dashboard/subscriptions/{$subscription->id}")
        ->assertRedirect(route('dashboard').'#subscriptions');

    expect(Subscription::find($subscription->id))->toBeNull()
        ->and(Subscription::withTrashed()->find($subscription->id)->deleted_at)->not->toBeNull();
});

it('does not let a user unsubscribe another user\'s subscription', function () {
    $owner = User::factory()->create();
    $subscription = Subscription::create(['user_id' => $owner->id, 'url' => 'https://example.com/feed.xml']);

    $this->actingAs(User::factory()->create())
        ->delete("/dashboard/subscriptions/{$subscription->id}")
        ->assertRedirect();

    expect(Subscription::find($subscription->id))->not->toBeNull();
});

it('updates language and timezone', function () {
    $user = User::factory()->create(['language' => 'en', 'timezone' => 'UTC']);

    $this->actingAs($user)
        ->post('/dashboard/preferences', ['language' => 'pt_BR', 'timezone' => 'America/Sao_Paulo'])
        ->assertRedirect(route('dashboard').'#settings')
        ->assertSessionHas('success');

    expect($user->fresh())
        ->language->toBe('pt_BR')
        ->timezone->toBe('America/Sao_Paulo');
});

it('rejects an unknown timezone', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->post('/dashboard/preferences', ['language' => 'en', 'timezone' => 'Mars/Olympus'])
        ->assertSessionHasErrors('timezone');

    expect($user->fresh()->timezone)->toBe('UTC');
});

it('changes the password', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $this->actingAs($user)
        ->post('/dashboard/password', [
            'current_password' => 'old-password',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ])
        ->assertSessionHas('success');

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('refuses a password change with the wrong current password', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $this->actingAs($user)
        ->post('/dashboard/password', [
            'current_password' => 'wrong',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('refuses a password change when the confirmation differs', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $this->actingAs($user)
        ->post('/dashboard/password', [
            'current_password' => 'old-password',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'other-password',
        ])
        ->assertSessionHasErrors('new_password');
});
