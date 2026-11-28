<?php

use App\Models\User;

it('forbids non-admin users from the admin panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('forbids inactive admins from the admin panel', function () {
    $user = User::factory()->create(['is_admin' => true, 'is_active' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('shows the admin dashboard to admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertSee('data-update-uri', false);
});

it('loads the users and subscriptions resources', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($admin)->get('/admin/subscriptions')->assertOk();
    $this->actingAs($admin)->get('/admin/feeds')->assertNotFound();
});

it('redirects guests to the filament login', function () {
    $this->get('/admin')->assertRedirect();
});

it('loads Livewire on the admin login page', function () {
    $this->get('/admin/login')->assertOk()->assertSee('data-update-uri', false);
});
