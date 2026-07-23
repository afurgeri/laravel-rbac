<?php

namespace App\Http\Controllers\Rbac;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\CrudIndexManager;
use Modules\Crud\CrudMutationManager;
use Modules\Crud\CrudOperation;
use Modules\Crud\CrudOperationGuard;
use Modules\Crud\CrudSchemaManager;
use Modules\Rbac\Models\MongoRole;

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
        $users = $index->paginate($definition, $request->integer('page', 1), $request->has('per_page') ? $request->integer('per_page') : null, $sort, $direction, $search, $filters);

        $users->through(fn (User $user): array => [
            'id' => (string) $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role_ids' => $user->roles->map(fn (MongoRole $role): string => (string) $role->getKey())->all(),
            'can' => ['update' => Gate::allows('update', $user), 'delete' => Gate::allows('delete', $user), 'show' => Gate::allows('view', $user)],
        ]);

        return Inertia::render('users/Index', [
            'crud' => $schema->for($definition, 'users', $sort, $direction, $search, $filters),
            'users' => $users,
            'roles' => $this->availableRoles(),
            'can' => ['create' => Gate::allows('create', User::class)],
        ]);
    }

    public function create(CrudSchemaManager $schema): Response
    {
        $definition = User::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Create);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        return Inertia::render('users/Create', [
            'crud' => $schema->for($definition, 'users'),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function store(Request $request, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = User::makeCrudDefinition();
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        /** @var User $user */
        $user = $mutations->create($definition, $request->all());
        $user->roles()->sync($this->validatedRoleIds($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    public function edit(User $user, CrudSchemaManager $schema): Response
    {
        $definition = User::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Update);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($user);
        }

        return Inertia::render('users/Edit', [
            'crud' => $schema->for($definition, 'users'),
            'user' => [
                'id' => (string) $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role_ids' => $user->roles()->pluck('id')->map(fn (mixed $id): string => (string) $id)->all(),
            ],
            'roles' => $this->availableRoles(),
        ]);
    }

    public function show(User $user, CrudSchemaManager $schema): Response
    {
        $definition = User::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Show);
        Gate::authorize('view', $user);

        return Inertia::render('users/Show', [
            'crud' => $schema->for($definition, 'users'),
            'user' => [
                'id' => (string) $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role_ids' => $user->roles()->pluck('id')->map(fn (mixed $id): string => (string) $id)->all(),
            ],
            'roles' => $this->availableRoles(),
        ]);
    }

    public function update(Request $request, User $user, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = User::makeCrudDefinition();
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($user);
        }

        $mutations->update($user, $definition, $request->all());
        $user->roles()->sync($this->validatedRoleIds($request));

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

        $user->forceFill(['password' => $validated['password']])->save();

        return to_route('users.index');
    }

    public function destroy(User $user, CrudMutationManager $mutations): RedirectResponse
    {
        $mutations->delete($user, User::makeCrudDefinition());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /**
     * @return list<string>
     */
    private function validatedRoleIds(Request $request): array
    {
        /** @var array{roles?: list<string>} $validated */
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists(MongoRole::class, 'id')],
        ]);

        return array_values(array_map('strval', $validated['roles'] ?? []));
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function availableRoles(): array
    {
        return MongoRole::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (MongoRole $role): array => ['id' => (string) $role->getKey(), 'name' => $role->name])
            ->values()->all();
    }
}
