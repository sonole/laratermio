<?php

use App\Filament\Pages\EditProfile;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Livewire\Livewire;

describe('admin panel configuration', function () {
    it('registers a default panel at /admin with login and the custom profile page', function () {
        $panel = Filament::getPanel('admin');

        expect($panel->getId())->toBe('admin')
            ->and($panel->isDefault())->toBeTrue()
            ->and($panel->getPath())->toBe('admin')
            ->and($panel->hasLogin())->toBeTrue()
            ->and($panel->getProfilePage())->toBe(EditProfile::class)
            ->and($panel->getPages())->toContain(Dashboard::class)
            ->and(route('filament.admin.auth.login', absolute: false))->toBe('/admin/login')
            ->and(route('filament.admin.auth.profile', absolute: false))->toBe('/admin/profile');
    });

    it('adds a "Visit site" link to the user menu', function () {
        $labels = collect(Filament::getPanel('admin')->getUserMenuItems())
            ->map(fn ($item) => $item->getLabel());

        expect($labels)->toContain('Visit site');
    });

    it('lets any user into the panel', function () {
        expect(User::factory()->make()->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
    });
});

describe('a guest', function () {
    it('is redirected from /admin to the login page', function () {
        $this->get('/admin')->assertRedirect('/admin/login');
    });

    it('is redirected to the login page from every admin page', function (string $path) {
        $this->get($path)->assertRedirect('/admin/login');
    })->with([
        '/admin/settings',
        '/admin/profile',
        '/admin/import-demo-content',
        '/admin/template-preview',
        '/admin/experiences',
        '/admin/contact-messages',
    ]);

    it('can open the login page', function () {
        $this->get('/admin/login')->assertOk()->assertSee('Sign in');
    });

    it('still sees the public site', function () {
        $this->get('/')->assertOk();
    });
});

describe('login', function () {
    it('signs in with valid credentials and lands on the dashboard', function () {
        $user = User::factory()->create(['must_change_password' => false]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(Filament::getPanel('admin')->getUrl());

        $this->assertAuthenticatedAs($user);
    });

    it('rejects a wrong password', function () {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'not-the-password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email'])
            ->assertNoRedirect();

        $this->assertGuest();
    });

    it('rejects an unknown email', function () {
        Livewire::test(Login::class)
            ->fillForm(['email' => 'nobody@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    });

    it('requires an email and a password', function () {
        Livewire::test(Login::class)
            ->fillForm(['email' => '', 'password' => ''])
            ->call('authenticate')
            ->assertHasFormErrors(['email' => 'required', 'password' => 'required']);
    });

    it('throttles repeated attempts', function () {
        $user = User::factory()->create();
        $login = Livewire::test(Login::class)->fillForm(['email' => $user->email, 'password' => 'wrong']);

        foreach (range(1, 6) as $attempt) {
            $login->call('authenticate');
        }

        $login->assertNotified();
        $this->assertGuest();

        // Even the right password is refused while throttled.
        $login->fillForm(['email' => $user->email, 'password' => 'password'])->call('authenticate');
        $this->assertGuest();
    });

    it('sends someone who is already signed in away from the login page', function () {
        actingAsAdmin();

        $this->get('/admin/login')->assertRedirect('/admin');
    });
});

describe('logout', function () {
    it('signs the user out and returns to the login page', function () {
        actingAsAdmin();

        $this->post('/admin/logout')->assertRedirect('/admin/login');

        $this->assertGuest();
        $this->get('/admin')->assertRedirect('/admin/login');
    });

    it('lets a user who must change their password sign out too', function () {
        $this->actingAs(User::factory()->create(['must_change_password' => true]));

        $this->post('/admin/logout')->assertRedirect('/admin/login');

        $this->assertGuest();
    });
});
