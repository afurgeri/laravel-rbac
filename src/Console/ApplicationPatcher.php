<?php

namespace Modules\Rbac\Console;

/**
 * Wires RBAC into the files of a freshly created Laravel starter kit application.
 *
 * Each patch reports whether it changed the file, found the integration already in place,
 * or could not recognise the file, which then has to be changed by hand.
 */
final class ApplicationPatcher
{
    public const ALREADY_PATCHED = 'already';

    public const PATCHED = 'patched';

    public const MANUAL = 'manual';

    /**
     * @return array{status: self::*, contents: string}
     */
    public function userModel(string $contents): array
    {
        if (str_contains($contents, 'HasRoles')) {
            return $this->already($contents);
        }

        if (preg_match('/^class User extends Authenticatable(?: implements ([^\n{]+))?\s*$/m', $contents, $classMatch) !== 1
            || preg_match('/^    use ([A-Za-z, ]+);$/m', $contents, $traitMatch, PREG_OFFSET_CAPTURE, (int) strpos($contents, $classMatch[0])) !== 1) {
            return $this->manual($contents);
        }

        $interfaces = $this->mergeNames($classMatch[1] ?? '', ['HasCrudDefinitionContract', 'HasPermissions']);
        $traits = $this->mergeNames($traitMatch[1][0], ['CanBeDeactivated', 'HasCrudDefinition', 'HasRoles']);

        $traitLine = $traitMatch[0][0];
        $traitOffset = $traitMatch[0][1];

        $contents = substr_replace($contents, "    use {$traits};\n\n    public static function crudDefinition(): string\n    {\n        return UserCrudDefinition::class;\n    }", $traitOffset, strlen($traitLine));
        $contents = str_replace($classMatch[0], "class User extends Authenticatable implements {$interfaces}", $contents);
        $contents = preg_replace('/^( \* @property string \$password)$/m', "$1\n * @property bool \$is_active", $contents, 1) ?? $contents;

        return $this->patched($this->addImports($contents, [
            'App\Crud\UserCrudDefinition',
            'Modules\Crud\Concerns\HasCrudDefinition',
            'Modules\Crud\Contracts\HasCrudDefinition as HasCrudDefinitionContract',
            'Modules\Rbac\Concerns\CanBeDeactivated',
            'Modules\Rbac\Concerns\HasRoles',
            'Modules\Rbac\Contracts\HasPermissions',
        ]));
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    public function databaseSeeder(string $contents): array
    {
        if (str_contains($contents, 'RbacPermissionSeeder')) {
            return $this->already($contents);
        }

        $runSignature = "    public function run(): void\n    {\n";

        if (! str_contains($contents, $runSignature)) {
            return $this->manual($contents);
        }

        $contents = str_replace([
            "        // User::factory(10)->create();\n\n",
            "        User::factory()->create([\n            'name' => 'Test User',\n            'email' => 'test@example.com',\n        ]);\n",
        ], '', $contents);

        $contents = str_replace($runSignature, $runSignature.implode("\n", [
            '        $this->call([',
            '            RbacPermissionSeeder::class,',
            '            AdminRoleSeeder::class,',
            '            AdminUserSeeder::class,',
            '        ]);',
            '',
        ]), $contents);

        $contents = preg_replace('/\]\);\n\s*\n(    \})/', "]);\n$1", $contents) ?? $contents;

        if (! preg_match('/\bUser::/', $contents)) {
            $contents = str_replace("use App\\Models\\User;\n", '', $contents);
        }

        return $this->patched($this->addImports($contents, [
            'Modules\Rbac\Database\Seeders\AdminRoleSeeder',
            'Modules\Rbac\Database\Seeders\AdminUserSeeder',
            'Modules\Rbac\Database\Seeders\RbacPermissionSeeder',
        ]));
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    public function bootstrapApp(string $contents): array
    {
        if (str_contains($contents, 'EnsureUserIsActive')) {
            return $this->already($contents);
        }

        $entry = "            EnsureUserIsActive::class,\n";

        if (str_contains($contents, "            HandleInertiaRequests::class,\n")) {
            $contents = str_replace("            HandleInertiaRequests::class,\n", $entry."            HandleInertiaRequests::class,\n", $contents);
        } elseif (str_contains($contents, "\$middleware->web(append: [\n")) {
            $contents = str_replace("\$middleware->web(append: [\n", "\$middleware->web(append: [\n".$entry, $contents);
        } else {
            return $this->manual($contents);
        }

        return $this->patched($this->addImports($contents, ['Modules\Rbac\Http\Middleware\EnsureUserIsActive']));
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    public function fortifyServiceProvider(string $contents): array
    {
        if (str_contains($contents, 'AuthenticateActiveUser')) {
            return $this->already($contents);
        }

        $anchor = "        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);\n";

        if (str_contains($contents, 'Fortify::authenticateUsing') || ! str_contains($contents, $anchor)) {
            return $this->manual($contents);
        }

        $contents = str_replace($anchor, $anchor."        Fortify::authenticateUsing(new AuthenticateActiveUser);\n", $contents);

        return $this->patched($this->addImports($contents, ['Modules\Rbac\Auth\AuthenticateActiveUser']));
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    public function inertiaMiddleware(string $contents): array
    {
        if (str_contains($contents, 'permissionNames')) {
            return $this->already($contents);
        }

        $anchor = "                'user' => \$request->user(),\n";

        if (! str_contains($contents, $anchor)) {
            return $this->manual($contents);
        }

        $contents = str_replace($anchor, $anchor.implode("\n", [
            "                'permissions' => \$request->user() instanceof HasPermissions",
            '                    ? Inertia::once(fn (): array => $request->user()->permissionNames())',
            "                        ->as('permissions.'.\$request->user()->getAuthIdentifier())",
            '                    : [],',
            '',
        ]), $contents);

        return $this->patched($this->addImports($contents, [
            'Inertia\Inertia',
            'Modules\Rbac\Contracts\HasPermissions',
        ]));
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    public function authTypes(string $contents): array
    {
        if (str_contains($contents, 'permissions: string[];')) {
            return $this->already($contents);
        }

        $anchor = "export type Auth = {\n    user: User;\n";

        if (! str_contains($contents, $anchor)) {
            return $this->manual($contents);
        }

        return $this->patched(str_replace($anchor, $anchor."    permissions: string[];\n", $contents));
    }

    /**
     * MySQL ids are numbers in the generated route helpers; MongoDB ids stay strings.
     */
    public function numericRouteKeys(string $contents): string
    {
        return preg_replace('/String\(((?:props\.)?(?:record|user|role)\.id)\)/', 'Number($1)', $contents) ?? $contents;
    }

    /**
     * @param  list<string>  $imports
     */
    private function addImports(string $contents, array $imports): string
    {
        $missing = array_values(array_filter(
            $imports,
            fn (string $import): bool => ! str_contains($contents, "use {$import};"),
        ));

        if ($missing === []) {
            return $contents;
        }

        if (preg_match('/^(?:use [^;\n]+;\n)+/m', $contents, $block, PREG_OFFSET_CAPTURE) === 1) {
            $existing = array_map(
                fn (string $line): string => substr($line, 4, -1),
                array_filter(explode("\n", $block[0][0])),
            );
            $names = array_values(array_unique([...$existing, ...$missing]));
            usort($names, fn (string $first, string $second): int => strcasecmp($first, $second));
            $sorted = implode('', array_map(fn (string $import): string => "use {$import};\n", $names));

            return substr_replace($contents, $sorted, $block[0][1], strlen($block[0][0]));
        }

        $lines = implode('', array_map(fn (string $import): string => "use {$import};\n", $missing));

        if (preg_match('/^namespace [^;]+;\n/m', $contents, $namespace, PREG_OFFSET_CAPTURE) === 1) {
            return substr_replace($contents, "\n".$lines, $namespace[0][1] + strlen($namespace[0][0]), 0);
        }

        return preg_replace('/^<\?php\n/', "<?php\n\n".$lines, $contents, 1) ?? $contents;
    }

    /**
     * @param  list<string>  $additions
     */
    private function mergeNames(string $existing, array $additions): string
    {
        $names = array_filter(array_map('trim', explode(',', $existing)));
        $names = array_values(array_unique([...$names, ...$additions]));

        usort($names, fn (string $first, string $second): int => strcasecmp($first, $second));

        return implode(', ', $names);
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    private function patched(string $contents): array
    {
        return ['status' => self::PATCHED, 'contents' => $contents];
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    private function already(string $contents): array
    {
        return ['status' => self::ALREADY_PATCHED, 'contents' => $contents];
    }

    /**
     * @return array{status: self::*, contents: string}
     */
    private function manual(string $contents): array
    {
        return ['status' => self::MANUAL, 'contents' => $contents];
    }
}
