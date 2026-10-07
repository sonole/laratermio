<?php

use App\Http\Middleware\RequirePasswordChange;
use App\Models\User;
use Illuminate\Http\Request;

function webForcedUser(): User
{
    $user = User::factory()->create(['must_change_password' => true]);

    test()->actingAs($user);

    return $user;
}

describe('a user who must change their password', function () {
    it('is redirected to the profile page from the admin dashboard', function () {
        webForcedUser();

        $this->get('/admin')->assertRedirect(route('filament.admin.auth.profile'));
    });

    it('is redirected to the profile page from any other admin page', function () {
        webForcedUser();

        $this->get('/admin/settings')->assertRedirect(route('filament.admin.auth.profile'));
    });

    it('remembers where they were heading', function () {
        webForcedUser();

        $this->get('/admin/settings')
            ->assertSessionHas('url.intended_after_password_change', url('/admin/settings'));
    });

    it('can open the profile page, which does not record a destination', function () {
        webForcedUser();

        $this->get(route('filament.admin.auth.profile'))
            ->assertOk()
            ->assertSessionMissing('url.intended_after_password_change');
    });

    it('can still use the public site', function () {
        webForcedUser();

        $this->get('/')->assertOk();
    });

    it('can log out', function () {
        webForcedUser();

        $this->post(route('filament.admin.auth.logout'));

        $this->assertGuest();
    });
});

describe('everyone else', function () {
    it('reaches the admin panel once the password has been changed', function () {
        actingAsAdmin();

        $this->get('/admin')->assertOk();
    });

    it('is not redirected away from other admin pages', function () {
        actingAsAdmin();

        $this->get('/admin/settings')->assertOk();
    });

    it('is sent to the login page when not signed in', function () {
        $this->get('/admin')->assertRedirect(route('filament.admin.auth.login'));
    });

    it('lets guests through the middleware itself', function () {
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => null);

        $response = (new RequirePasswordChange)->handle($request, fn () => response('passed'));

        expect($response->getContent())->toBe('passed');
    });

    it('lets a user without the flag through the middleware itself', function () {
        $request = Request::create('/admin');
        $request->setUserResolver(fn () => User::factory()->make(['must_change_password' => false]));

        $response = (new RequirePasswordChange)->handle($request, fn () => response('passed'));

        expect($response->getContent())->toBe('passed');
    });
});
