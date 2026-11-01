<?php

use Modules\Rbac\Console\ApplicationPatcher;

dataset('starter kit files', [
    'user model' => ['userModel', 'User.php'],
    'database seeder' => ['databaseSeeder', 'DatabaseSeeder.php'],
    'bootstrap app' => ['bootstrapApp', 'app.php'],
    'fortify provider' => ['fortifyServiceProvider', 'FortifyServiceProvider.php'],
    'inertia middleware' => ['inertiaMiddleware', 'HandleInertiaRequests.php'],
    'auth types' => ['authTypes', 'auth.ts'],
]);

function starterKitFixture(string $directory, string $file): string
{
    return (string) file_get_contents(dirname(__DIR__, 2)."/Fixtures/{$directory}/{$file}");
}

test('it wires rbac into the starter kit file', function (string $patch, string $file) {
    $result = (new ApplicationPatcher)->{$patch}(starterKitFixture('starter-kit', $file));

    expect($result['status'])->toBe(ApplicationPatcher::PATCHED)
        ->and($result['contents'])->toBe(starterKitFixture('starter-kit-patched', $file));
})->with('starter kit files');

test('it leaves an already integrated file untouched', function (string $patch, string $file) {
    $patched = starterKitFixture('starter-kit-patched', $file);

    expect((new ApplicationPatcher)->{$patch}($patched))
        ->toBe(['status' => ApplicationPatcher::ALREADY_PATCHED, 'contents' => $patched]);
})->with('starter kit files');

test('it asks for a manual change when the file does not look like the starter kit', function (string $patch, string $contents) {
    expect((new ApplicationPatcher)->{$patch}($contents))
        ->toBe(['status' => ApplicationPatcher::MANUAL, 'contents' => $contents]);
})->with([
    'custom user model' => ['userModel', "<?php\n\nclass Account extends Model\n{\n}\n"],
    'seeder without run' => ['databaseSeeder', "<?php\n\nclass DatabaseSeeder extends Seeder\n{\n}\n"],
    'bootstrap without web group' => ['bootstrapApp', "<?php\n\nreturn Application::configure()->create();\n"],
    'fortify with its own login callback' => ['fortifyServiceProvider', str_replace(
        "        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);\n",
        "        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);\n        Fortify::authenticateUsing(fn () => null);\n",
        starterKitFixture('starter-kit', 'FortifyServiceProvider.php'),
    )],
    'inertia middleware without auth user' => ['inertiaMiddleware', "<?php\n\nclass HandleInertiaRequests\n{\n}\n"],
    'custom auth type' => ['authTypes', "export type Auth = { user: User };\n"],
]);

test('it turns string route keys into numbers for sql ids', function () {
    $page = 'updateUser.form.patch(String(record.id)); editRole(String(role.id)); patch(String(props.user.id)); String(other.id)';

    expect((new ApplicationPatcher)->numericRouteKeys($page))
        ->toBe('updateUser.form.patch(Number(record.id)); editRole(Number(role.id)); patch(Number(props.user.id)); String(other.id)');
});
