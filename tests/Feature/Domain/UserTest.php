<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Hash;

describe('canAccessPanel()', function () {
    it('is a Filament user', function () {
        expect(new User)->toBeInstanceOf(FilamentUser::class);
    });

    it('allows any user into the admin panel', function () {
        $user = User::factory()->create();

        expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
    });
});

describe('attributes', function () {
    it('hashes the password when it is set', function () {
        $user = User::factory()->create(['password' => 'secret-pass']);

        expect($user->password)->not->toBe('secret-pass')
            ->and(Hash::check('secret-pass', $user->password))->toBeTrue();
    });

    it('hides the password and remember token when serialised', function () {
        $array = User::factory()->create()->toArray();

        expect($array)->not->toHaveKeys(['password', 'remember_token']);
    });

    it('casts must_change_password to a boolean', function () {
        $user = User::factory()->create(['must_change_password' => 1])->fresh();

        expect($user->must_change_password)->toBeTrue();
    });
});

describe('initials()', function () {
    it('uses the first letters of the first two names', function (string $name, string $initials) {
        expect(User::factory()->make(['name' => $name])->initials())->toBe($initials);
    })->with([
        ['Alex Example', 'AE'],
        ['Alex', 'A'],
        ['Alex van Example', 'Av'],
    ]);
});
