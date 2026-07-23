<?php

namespace App\Http\Controllers\Rbac;

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
use Modules\Rbac\Models\MongoPermission;
use Modules\Rbac\Models\MongoRole;

class RoleController
{
    public function index(Request $request, CrudIndexManager $index, CrudSchemaManager $schema): Response
    {
        $definition = MongoRole::makeCrudDefinition();
        $sort = $request->string('sort')->toString() ?: null;
        $direction = $request->string('direction', 'asc')->toString();
        $search = $request->string('search')->toString() ?: null;
        $filters = $request->array('filters');

        /** @var LengthAwarePaginator<int, MongoRole> $roles */
        $roles = $index->paginate($definition, $request->integer('page', 1), $request->has('per_page') ? $request->integer('per_page') : null, $sort, $direction, $search, $filters);

        $roles->through(fn (MongoRole $role): array => [
            'id' => (string) $role->getKey(),
            'name' => $role->name,
            'permission_ids' => $role->permissions->map(fn (MongoPermission $permission): string => (string) $permission->getKey())->all(),
            'can' => ['update' => Gate::allows('update', $role), 'delete' => Gate::allows('delete', $role), 'show' => Gate::allows('view', $role)],
        ]);

        return Inertia::render('roles/Index', [
            'crud' => $schema->for($definition, 'roles', $sort, $direction, $search, $filters),
            'roles' => $roles,
            'permissions' => $this->availablePermissions(),
            'can' => ['create' => Gate::allows('create', MongoRole::class)],
        ]);
    }

    public function create(CrudSchemaManager $schema): Response
    {
        $definition = MongoRole::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Create);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        return Inertia::render('roles/Create', [
            'crud' => $schema->for($definition, 'roles'),
            'permissions' => $this->availablePermissions(),
        ]);
    }

    public function store(Request $request, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = MongoRole::makeCrudDefinition();
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        /** @var MongoRole $role */
        $role = $mutations->create($definition, $request->all());
        $role->permissions()->sync($this->validatedPermissionIds($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    public function edit(MongoRole $role, CrudSchemaManager $schema): Response
    {
        $definition = MongoRole::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Update);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($role);
        }

        return Inertia::render('roles/Edit', [
            'crud' => $schema->for($definition, 'roles'),
            'role' => [
                'id' => (string) $role->getKey(),
                'name' => $role->name,
                'permission_ids' => $role->permissions()->pluck('id')->map(fn (mixed $id): string => (string) $id)->all(),
            ],
            'permissions' => $this->availablePermissions(),
        ]);
    }

    public function show(MongoRole $role, CrudSchemaManager $schema): Response
    {
        $definition = MongoRole::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Show);
        Gate::authorize('view', $role);

        return Inertia::render('roles/Show', [
            'crud' => $schema->for($definition, 'roles'),
            'role' => [
                'id' => (string) $role->getKey(),
                'name' => $role->name,
                'permission_ids' => $role->permissions()->pluck('id')->map(fn (mixed $id): string => (string) $id)->all(),
            ],
            'permissions' => $this->availablePermissions(),
        ]);
    }

    public function update(Request $request, MongoRole $role, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = MongoRole::makeCrudDefinition();
        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($role);
        }

        $mutations->update($role, $definition, $request->all());
        $role->permissions()->sync($this->validatedPermissionIds($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    public function destroy(MongoRole $role, CrudMutationManager $mutations): RedirectResponse
    {
        $mutations->delete($role, MongoRole::makeCrudDefinition());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('roles.index');
    }

    /**
     * @return list<string>
     */
    private function validatedPermissionIds(Request $request): array
    {
        /** @var array{permissions?: list<string>} $validated */
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists(MongoPermission::class, 'id')],
        ]);

        return array_values(array_map('strval', $validated['permissions'] ?? []));
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function availablePermissions(): array
    {
        return MongoPermission::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (MongoPermission $permission): array => ['id' => (string) $permission->getKey(), 'name' => $permission->name])
            ->values()->all();
    }
}
