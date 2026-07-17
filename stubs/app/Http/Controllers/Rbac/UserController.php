<?php

namespace App\Http\Controllers\Rbac;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\CrudIndexManager;
use Modules\Crud\CrudMutationManager;
use Modules\Crud\CrudSchemaManager;
use Modules\Rbac\Models\Role;

class UserController
{
    public function index(Request $request, CrudIndexManager $index, CrudSchemaManager $schema): Response
    {
        $definition = User::makeCrudDefinition();

        $sort = $request->string('sort')->toString() ?: null;
        $direction = $request->string('direction', 'asc')->toString();
        $search = $request->string('search')->toString() ?: null;
        $filters = $request->array('filters');

        /** @var LengthAwarePaginator<int, User> $users */
        $users = $index->paginate(
            definition: $definition,
            page: $request->integer('page', 1),
            perPage: $request->integer('per_page', 15),
            sort: $sort,
            direction: $direction,
            search: $search,
            filters: $filters,
        );

        $users->through(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_ids' => $user->roles->pluck('id')->all(),
            'can' => [
                'update' => Gate::allows('update', $user),
                'delete' => Gate::allows('delete', $user),
            ],
        ]);

        return Inertia::render('users/Index', [
            'crud' => $schema->for($definition, 'users', $sort, $direction, $search, $filters),
            'users' => $users,
            'roles' => $this->availableRoles(),
            'can' => [
                'create' => Gate::allows('create', User::class),
            ],
        ]);
    }

    public function store(Request $request, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = User::makeCrudDefinition();

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        $roleIds = $this->validatedRoleIds($request);

        $user = $mutations->create(User::makeCrudDefinition(), $request->all());

        /** @var User $user */
        $user->roles()->sync($roleIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function update(Request $request, User $user, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = User::makeCrudDefinition();

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($user);
        }

        $roleIds = $this->validatedRoleIds($request);

        $mutations->update($user, User::makeCrudDefinition(), $request->all());
        $user->roles()->sync($roleIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        /** @var array{password: string} $validated */
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        return to_route('users.index');
    }

    public function destroy(User $user, CrudMutationManager $mutations): RedirectResponse
    {
        $mutations->delete($user, User::makeCrudDefinition());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /**
     * @return list<int>
     */
    private function validatedRoleIds(Request $request): array
    {
        /** @var array{roles?: list<int|string>} $validated */
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        return array_map('intval', $validated['roles'] ?? []);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function availableRoles(): array
    {
        return array_values(Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])
            ->values()
            ->all());
    }
}
