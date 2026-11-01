<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Rbac\Auth\AuthenticateActiveUser;
use Modules\Rbac\Http\Middleware\EnsureUserIsActive;
use Tests\Fixtures\User;

beforeEach(function () {
    migrateWithUsers();

    Route::get('login', fn () => 'login')->name('login');
    Route::middleware(['web', EnsureUserIsActive::class])->get('protected', fn () => 'protected');
});

function createUser(array $attributes = []): User
{
    return User::query()->create([
        'name' => 'Seller',
        'email' => 'seller@example.com',
        'password' => 'password',
        ...$attributes,
    ]);
}

test('new users are active by default', function () {
    expect(createUser()->fresh()->is_active)->toBeTrue();
});

test('the active flag can be mass assigned', function () {
    expect(createUser(['is_active' => false])->fresh()->is_active)->toBeFalse();
});

test('active users reach the page', function () {
    $this->actingAs(createUser())->get('protected')->assertOk()->assertSee('protected');
});

test('users deactivated during an open session are logged out', function () {
    $this->actingAs(createUser(['is_active' => false]))
        ->get('protected')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'Your account has been deactivated.']);

    $this->assertGuest();
});

test('users stored before the flag existed count as active', function () {
    $user = createUser();
    $user->setRawAttributes([...$user->getAttributes(), 'is_active' => null]);

    expect($user->isDeactivated())->toBeFalse();
});

test('the login callback returns active users with valid credentials', function () {
    $user = createUser();

    $authenticated = (new AuthenticateActiveUser)(request()->merge(['email' => $user->email, 'password' => 'password']));

    expect($authenticated?->is($user))->toBeTrue();
});

test('the login callback rejects deactivated users', function () {
    $user = createUser(['is_active' => false]);

    (new AuthenticateActiveUser)(request()->merge(['email' => $user->email, 'password' => 'password']));
})->throws(ValidationException::class, 'Your account has been deactivated.');

test('the login callback gives no hint about deactivated users with a wrong password', function () {
    $user = createUser(['is_active' => false]);

    expect((new AuthenticateActiveUser)(request()->merge(['email' => $user->email, 'password' => 'wrong-password'])))->toBeNull();
});
