<?php

use App\Filament\Pages\EditProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = actingAsAdmin();
});

describe('profile page', function () {
    it('renders for a signed-in admin', function () {
        $this->get('/admin/profile')
            ->assertOk()
            ->assertSee($this->admin->email);
    });

    it('is the page the panel uses for the profile', function () {
        expect(filament()->getProfilePage())->toBe(EditProfile::class);
    });

    it('fills the form with the current name and email but never the password', function () {
        Livewire::test(EditProfile::class)
            ->assertFormSet([
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'password' => null,
            ]);
    });
});

describe('name and email', function () {
    it('updates the name', function () {
        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($this->admin->refresh()->name)->toBe('New Name');
    });

    it('updates the email when the current password is given', function () {
        Livewire::test(EditProfile::class)
            ->fillForm(['email' => 'changed@example.com', 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($this->admin->refresh()->email)->toBe('changed@example.com');
    });

    it('asks for the current password before changing the email', function () {
        $original = $this->admin->email;

        Livewire::test(EditProfile::class)
            ->fillForm(['email' => 'changed@example.com', 'currentPassword' => ''])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);

        expect($this->admin->refresh()->email)->toBe($original);
    });

    it('validates the name and email', function (array $input, array $errors) {
        Livewire::test(EditProfile::class)
            ->fillForm($input)
            ->call('save')
            ->assertHasFormErrors($errors);
    })->with([
        'name required' => [['name' => ''], ['name' => 'required']],
        'name too long' => [['name' => str_repeat('a', 256)], ['name' => 'max']],
        'email required' => [['email' => ''], ['email' => 'required']],
        'email format' => [['email' => 'not-an-email'], ['email' => 'email']],
    ]);

    it('refuses an email that another user already has', function () {
        $other = User::factory()->create();

        Livewire::test(EditProfile::class)
            ->fillForm(['email' => $other->email, 'currentPassword' => 'password'])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);
    });

    it('lets the user keep their own email', function () {
        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Same Email'])
            ->call('save')
            ->assertHasNoFormErrors();
    });
});

describe('password', function () {
    it('changes the password and keeps the user signed in', function () {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'a-brand-new-secret',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.password', null)
            ->assertSet('data.passwordConfirmation', null);

        expect(Hash::check('a-brand-new-secret', $this->admin->refresh()->password))->toBeTrue();
        $this->get('/admin')->assertOk();
    });

    it('stores a hash, not the plain text', function () {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'a-brand-new-secret',
                'currentPassword' => 'password',
            ])
            ->call('save');

        expect($this->admin->refresh()->password)->not->toBe('a-brand-new-secret')
            ->and(Hash::isHashed($this->admin->password))->toBeTrue();
    });

    it('requires the confirmation to match', function () {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'different',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasFormErrors(['password' => 'same']);

        expect(Hash::check('password', $this->admin->refresh()->password))->toBeTrue();
    });

    it('requires the current password to be correct', function () {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'a-brand-new-secret',
                'currentPassword' => 'wrong-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        expect(Hash::check('password', $this->admin->refresh()->password))->toBeTrue();
    });

    it('leaves the password alone when the field is empty', function () {
        $hash = $this->admin->password;

        Livewire::test(EditProfile::class)
            ->fillForm(['name' => 'Only Name', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($this->admin->refresh()->password)->toBe($hash);
    });

    it('does not redirect a user who was never forced to change it', function () {
        Livewire::test(EditProfile::class)
            ->fillForm([
                'password' => 'a-brand-new-secret',
                'passwordConfirmation' => 'a-brand-new-secret',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertNoRedirect();
    });
});
