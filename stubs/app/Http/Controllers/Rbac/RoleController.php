<?php

namespace App\Http\Controllers\Rbac;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\CrudIndexManager;
use Modules\Crud\CrudMutationManager;
use Modules\Crud\CrudOperation;
use Modules\Crud\CrudOperationGuard;
use Modules\Crud\CrudSchemaManager;
use Modules\Rbac\Models\Permission;
use Modules\Rbac\Models\Role;

class RoleController
{
    public function index(Request $request, CrudIndexManager $index, CrudSchemaManager $schema): Response
    {
        $definition = Role::makeCrudDefinition();

        $sort = $request->string('sort')->toString() ?: null;
        $direction = $request->string('direction', 'asc')->toString();
        $search = $request->string('search')->toString() ?: null;
        $filters = $request->array('filters');

        /** @var LengthAwarePaginator<int, Role> $roles */
        $roles = $index->paginate(
            definition: $definition,
            page: $request->integer('page', 1),
            perPage: $request->has('per_page') ? $request->integer('per_page') : null,
            sort: $sort,
            direction: $direction,
            search: $search,
            filters: $filters,
        );

        $roles->through(fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'permission_ids' => $role->permissions->pluck('id')->all(),
            'can' => [
                'update' => Gate::allows('update', $role),
                'delete' => Gate::allows('delete', $role),
                'show' => Gate::allows('view', $role),
            ],
        ]);

        return Inertia::render('roles/Index', [
            'crud' => $schema->for($definition, 'roles', $sort, $direction, $search, $filters),
            'roles' => $roles,
            'permissions' => $this->availablePermissions(),
            'can' => [
                'create' => Gate::allows('create', Role::class),
            ],
        ]);
    }

    public function create(CrudSchemaManager $schema): Response
    {
        $definition = Role::makeCrudDefinition();
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
        $definition = Role::makeCrudDefinition();

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeCreate();
        }

        $permissionIds = $this->validatedPermissionIds($request);

        $role = $mutations->create(Role::makeCrudDefinition(), $request->all());

        /** @var Role $role */
        $role->permissions()->sync($permissionIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    public function edit(Role $role, CrudSchemaManager $schema): Response
    {
        $definition = Role::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Update);

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($role);
        }

        return Inertia::render('roles/Edit', [
            'crud' => $schema->for($definition, 'roles'),
            'role' => [
                'id' => $role->getKey(),
                'name' => $role->name,
                'permission_ids' => $role->permissions()->pluck('id')->all(),
            ],
            'permissions' => $this->availablePermissions(),
        ]);
    }

    public function show(Role $role, CrudSchemaManager $schema): Response
    {
        $definition = Role::makeCrudDefinition();
        CrudOperationGuard::ensureEnabled($definition, CrudOperation::Show);
        Gate::authorize('view', $role);

        return Inertia::render('roles/Show', [
            'crud' => $schema->for($definition, 'roles'),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permission_ids' => $role->permissions()->pluck('id')->all(),
            ],
            'permissions' => $this->availablePermissions(),
        ]);
    }

    public function update(Request $request, Role $role, CrudMutationManager $mutations): RedirectResponse
    {
        $definition = Role::makeCrudDefinition();

        if ($definition instanceof AuthorizesCrudMutations) {
            $definition->authorizeUpdate($role);
        }

        $permissionIds = $this->validatedPermissionIds($request);

        $mutations->update($role, Role::makeCrudDefinition(), $request->all());
        $role->permissions()->sync($permissionIds);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    public function destroy(Role $role, CrudMutationManager $mutations): RedirectResponse
    {
        $mutations->delete($role, Role::makeCrudDefinition());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('roles.index');
    }

    /**
     * @return list<int>
     */
    private function validatedPermissionIds(Request $request): array
    {
        /** @var array{permissions?: list<int|string>} $validated */
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        return array_map('intval', $validated['permissions'] ?? []);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function availablePermissions(): array
    {
        return array_values(Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
            ])
            ->values()
            ->all());
    }
}
