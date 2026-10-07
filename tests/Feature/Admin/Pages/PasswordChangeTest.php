<?php

use App\Filament\Pages\EditProfile;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function adminPasswordChangeUser(bool $mustChange = true): User
{
    $user = User::factory()->create(['must_change_password' => $mustChange]);

    test()->actingAs($user);

    return $user;
}

describe('forced password change', function () {
    it('redirects a user who must change their password to the profile page', function () {
        adminPasswordChangeUser();

        $this->get('/admin')->assertRedirect(route('filament.admin.auth.profile'));
    });

    it('catches them right after they sign in', function () {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->get('/admin')->assertRedirect(route('filament.admin.auth.profile'));
    });

    it('blocks every other admin page until the password is changed', function (string $path) {
        adminPasswordChangeUser();

        $this->get($path)->assertRedirect(route('filament.admin.auth.profile'));
    })->with([
        '/admin/settings',
        '/admin/import-demo-content',
        '/admin/template-preview',
        '/admin/experiences',
        '/admin/projects',
    ]);

    it('lets the profile page itself load', function () {
        adminPasswordChangeUser();

        $this->get('/admin/profile')->assertOk();
    });

    it('remembers the page that was asked for', function () {
        adminPasswordChangeUser();

        $this->get('/admin/settings');

        expect(session('url.intended_after_password_change'))->toBe(url('/admin/settings'));
    });

    it('does not interfere with users who already changed their password', function () {
        adminPasswordChangeUser(mustChange: false);

        $this->get('/admin/settings')->assertOk();
        $this->get('/admin/template-preview')->assertOk();
    });

    it('seeds the admin from the system seeder with the flag switched on', function () {
        config(['app.admin.email' => 'owner@example.com', 'app.admin.name' => 'Owner']);

        seedSystem();

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        expect($owner->must_change_password)->toBeTrue()
            ->and(Hash::check('password', $owner->password))->toBeTrue();
    });
});

describe('changing the password', function () {
    it('clears the flag and sends the user back to the page they wanted', function () {
        $user = adminPasswordChangeUser();
        $this->get('/admin/settings');

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'a-brand-new-secret',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(url('/admin/settings'));

        $user->refresh();

        expect($user->must_change_password)->toBeFalse()
            ->and(Hash::check('a-brand-new-secret', $user->password))->toBeTrue()
            ->and(session()->has('url.intended_after_password_change'))->toBeFalse();
    });

    it('unlocks the rest of the admin panel', function () {
        adminPasswordChangeUser();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'a-brand-new-secret',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/admin/settings')->assertOk();
    });

    it('keeps the flag while the password is left unchanged', function () {
        $user = adminPasswordChangeUser();

        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Renamed Admin'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $user->refresh();

        expect($user->name)->toBe('Renamed Admin')
            ->and($user->must_change_password)->toBeTrue();
        $this->get('/admin/settings')->assertRedirect(route('filament.admin.auth.profile'));
    });

    it('keeps the flag when the new password is rejected', function () {
        $user = adminPasswordChangeUser();

        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'something-else-entirely',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);

        expect($user->refresh()->must_change_password)->toBeTrue();
    });
});
