<?php

use App\Models\Device;
use App\Models\EpisodeAction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();

    $this->user = User::factory()->create([
        'name' => 'testuser',
        'password' => 'secret-password',
    ]);
});

// ------------------------------------------------------------------ auth

it('logs in via basic auth and returns the legacy envelope', function () {
    $this->postJson('/api/2/auth/testuser/login.json', [], ['Authorization' => 'Basic '.base64_encode('testuser:secret-password')])
        ->assertStatus(200)
        ->assertJson(['code' => 200]);
});

it('rejects wrong credentials on login', function () {
    $this->call('POST', '/api/2/auth/testuser/login.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'wrong',
    ])->assertStatus(401);
});

it('logs out via the api', function () {
    $this->call('POST', '/api/2/auth/testuser/logout.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200);
});

it('requires credentials for protected endpoints', function () {
    $this->getJson('/api/2/devices/testuser.json')
        ->assertStatus(401)
        ->assertJson(['code' => 401]);
});

it('authenticates with the username__token url credential', function () {
    $token = 'testuser__'.substr(sha1($this->user->password), 0, 10);

    $this->getJson("/api/2/devices/{$token}.json")
        ->assertStatus(200);
});

it('rejects an invalid url token', function () {
    $this->getJson('/api/2/devices/testuser__0000000000.json')
        ->assertStatus(401);
});

it('authenticates with basic auth stripping the __suffix', function () {
    $this->call('GET', '/api/2/devices/testuser.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser__antennapod',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200);
});

// ---------------------------------------------------------------- devices

it('upserts a device and lists it', function () {
    $this->call('POST', '/api/2/devices/testuser/phone.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ], json_encode(['caption' => 'My Phone', 'type' => 'mobile']))->assertStatus(200);

    $device = Device::where('identifier', 'phone')->first();
    expect($device)->not->toBeNull()
        ->and($device->name)->toBe('My Phone')
        ->and($device->data)->toHaveKey('subscriptions', 0);

    $response = $this->call('GET', '/api/2/devices/testuser.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200);

    $devices = $response->json();
    expect($devices)->toHaveCount(1)
        ->and($devices[0]['id'])->toBe('phone')
        ->and($devices[0]['deviceid'])->toBe('phone');
});

// ----------------------------------------------------------- subscriptions

it('bulk adds subscriptions via PUT txt and lists them via v1', function () {
    $this->call('PUT', '/api/2/subscriptions/testuser/default.txt', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
        'CONTENT_TYPE' => 'text/plain',
    ], "https://example.com/feed.xml?utm_source=x\nhttps://OTHER.org/rss/\nnot-a-url\n")
        ->assertStatus(200);

    $urls = Subscription::pluck('url')->all();

    expect($urls)->toHaveCount(2)
        ->toContain('https://example.com/feed.xml')
        ->toContain('https://other.org/rss');

    $response = $this->call('GET', '/subscriptions/testuser.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200);

    expect($response->json())->toHaveCount(2);
});

it('restores a soft-deleted subscription via PUT bulk add', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    // Add, remove, then bulk-add the same URL — the subscription should be restored
    $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['add' => ['https://example.com/a.xml']]));
    $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['remove' => ['https://example.com/a.xml']]));

    $this->call('PUT', '/api/2/subscriptions/testuser/default.txt', [], [], [], array_merge($auth, ['CONTENT_TYPE' => 'text/plain']),
        "https://example.com/a.xml\n")->assertStatus(200);

    $sub = Subscription::where('url', 'https://example.com/a.xml')->first();
    expect($sub->deleted_at)->toBeNull();
});

it('syncs add/remove deltas and reports them since a timestamp', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    $response = $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['add' => ['https://example.com/a.xml', 'https://example.com/b.xml']]))
        ->assertStatus(200);

    expect($response->json())->toHaveKeys(['timestamp', 'update_urls']);

    $delta = $this->call('GET', '/api/2/subscriptions/testuser/default.json?since=0', [], [], [], $auth)
        ->assertStatus(200)->json();

    expect($delta['add'])->toHaveCount(2)
        ->and($delta['remove'])->toHaveCount(0)
        ->and($delta['update_urls'])->toBe([]);

    $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['remove' => ['https://example.com/a.xml']]))
        ->assertStatus(200);

    $delta = $this->call('GET', '/api/2/subscriptions/testuser/default.json?since=0', [], [], [], $auth)
        ->json();

    // Legacy semantics: "add" lists only non-deleted rows changed since,
    // so the removed URL moves from "add" to "remove".
    expect($delta['add'])->toHaveCount(1)
        ->and($delta['remove'])->toHaveCount(1)
        ->and($delta['remove'][0])->toBe('https://example.com/a.xml');
});

it('restores a soft-deleted subscription on re-add', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['add' => ['https://example.com/a.xml']]));
    $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['remove' => ['https://example.com/a.xml']]));
    $this->call('POST', '/api/2/subscriptions/testuser/default.json', [], [], [], $auth,
        json_encode(['add' => ['https://example.com/a.xml']]));

    $sub = Subscription::where('url', 'https://example.com/a.xml')->first();
    expect($sub->deleted_at)->toBeNull();
    expect(Subscription::withTrashed()->count())->toBe(1);
});

it('serves the v1 opml format', function () {
    Subscription::create(['user_id' => $this->user->id, 'url' => 'https://example.com/a.xml']);

    $response = $this->call('GET', '/subscriptions/testuser.opml', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200);

    expect($response->headers->get('content-type'))->toContain('text/x-opml')
        ->and($response->getContent())->toContain('xmlUrl="https://example.com/a.xml"');
});

// -------------------------------------------------------------- episodes

it('stores episode actions and returns them since a timestamp', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    $payload = [[
        'podcast' => 'https://example.com/feed.xml',
        'episode' => 'https://example.com/ep1.mp3',
        'action' => 'play',
        'timestamp' => '2026-07-20T10:00:00Z',
        'position' => 120,
        'total' => 3600,
        'device' => 'phone',
    ]];

    $this->call('POST', '/api/2/episodes/testuser.json', [], [], [], $auth, json_encode($payload))
        ->assertStatus(200)
        ->assertJsonStructure(['timestamp', 'update_urls']);

    $action = EpisodeAction::first();
    expect($action)->not->toBeNull()
        ->and($action->action)->toBe('play')
        ->and($action->url)->toBe('https://example.com/ep1.mp3')
        ->and($action->data['position'])->toBe(120)
        ->and($action->changed_at->toDateTimeString())->toBe('2026-07-20 10:00:00');

    // Subscription auto-created on the fly
    expect(Subscription::where('url', 'https://example.com/feed.xml')->exists())->toBeTrue();

    $response = $this->call('GET', '/api/2/episodes/testuser.json?since=0', [], [], [], $auth)
        ->assertStatus(200)->json();

    expect($response['actions'])->toHaveCount(1)
        ->and($response['actions'][0]['timestamp'])->toBe('2026-07-20T10:00:00Z')
        ->and($response['actions'][0]['podcast'])->toBe('https://example.com/feed.xml')
        ->and($response['actions'][0]['episode'])->toBe('https://example.com/ep1.mp3')
        ->and($response['actions'][0]['position'])->toBe(120);
});

it('dedups identical actions at identical timestamps', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    $action = [
        'podcast' => 'https://example.com/feed.xml',
        'episode' => 'https://example.com/ep1.mp3',
        'action' => 'download',
        'timestamp' => '2026-07-20T10:00:00Z',
    ];

    $this->call('POST', '/api/2/episodes/testuser.json', [], [], [], $auth, json_encode([$action]));
    $this->call('POST', '/api/2/episodes/testuser.json', [], [], [], $auth, json_encode([$action]));

    expect(EpisodeAction::count())->toBe(1);
});

it('skips invalid actions but keeps valid ones', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    $payload = [
        ['podcast' => 'https://example.com/feed.xml', 'episode' => 'https://example.com/e1.mp3', 'action' => 'bogus'],
        ['podcast' => 'https://example.com/feed.xml', 'episode' => 'https://example.com/e2.mp3', 'action' => 'new'],
        ['podcast' => 'not-a-url', 'episode' => 'https://example.com/e3.mp3', 'action' => 'play'],
    ];

    $this->call('POST', '/api/2/episodes/testuser.json', [], [], [], $auth, json_encode($payload))
        ->assertStatus(200);

    expect(EpisodeAction::count())->toBe(1)
        ->and(EpisodeAction::first()->action)->toBe('new');
});

// ------------------------------------------------------------- nextcloud

it('starts the nextcloud login flow v2', function () {
    $response = $this->postJson('/index.php/login/v2')->assertStatus(200)->json();

    expect($response)->toHaveKeys(['poll', 'login'])
        ->and($response['poll'])->toHaveKeys(['token', 'endpoint'])
        ->and($response['login'])->toContain('login?token=');
});

it('authenticates gpoddersync requests with the app password', function () {
    $token = 'abcdef0123456789';
    $appPassword = $token.':'.sha1($this->user->password.$token);

    Subscription::create(['user_id' => $this->user->id, 'url' => 'https://example.com/a.xml']);

    $response = $this->call('GET', '/index.php/apps/gpoddersync/subscriptions?since=0', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => $appPassword,
    ])->assertStatus(200)->json();

    expect($response)->toHaveKeys(['add', 'remove', 'update_urls', 'timestamp'])
        ->and($response['add'])->toContain('https://example.com/a.xml');
});

it('rejects gpoddersync requests with a bad app password', function () {
    $this->call('GET', '/index.php/apps/gpoddersync/subscriptions', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'token:invalid',
    ])->assertStatus(401);
});

// ------------------------------------------------------------------ stubs

it('returns empty arrays for stub sections', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    // v1 stubs also require auth in the legacy app
    $this->call('GET', '/suggestions/10.json', [], [], [], $auth)->assertStatus(200)->assertExactJson([]);
    $this->call('GET', '/toplist/10.json', [], [], [], $auth)->assertStatus(200)->assertExactJson([]);

    $this->call('GET', '/api/2/tags/testuser.json', [], [], [], [
        'PHP_AUTH_USER' => 'testuser',
        'PHP_AUTH_PW' => 'secret-password',
    ])->assertStatus(200)->assertExactJson([]);
});

it('returns 501 for updates and 503 for settings/lists', function () {
    $auth = ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'secret-password'];

    $this->call('GET', '/api/2/updates/testuser/default.json', [], [], [], $auth)->assertStatus(501);
    $this->call('GET', '/api/2/settings/testuser.json', [], [], [], $auth)->assertStatus(503);
    $this->call('GET', '/api/2/lists/testuser.json', [], [], [], $auth)->assertStatus(503);
});

it('returns 501 for jsonp/xml formats', function () {
    $this->getJson('/api/2/episodes/testuser.jsonp')->assertStatus(501);
});
