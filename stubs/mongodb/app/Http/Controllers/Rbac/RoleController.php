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
        $roles = $index->paginate($definition, $request->integer('page', 1), $request->integer('per_page', 15), $sort, $direction, $search, $filters);

        $roles->through(fn (MongoRole $role): array => [
            'id' => (string) $role->getKey(),
            'name' => $role->name,
            'permission_ids' => $role->permissions->map(fn (MongoPermission $permission): string => (string) $permission->getKey())->all(),
            'can' => ['update' => Gate::allows('update', $role), 'delete' => Gate::allows('delete', $role)],
        ]);

        return Inertia::render('roles/Index', [
            'crud' => $schema->for($definition, 'roles', $sort, $direction, $search, $filters),
            'roles' => $roles,
            'permissions' => $this->availablePermissions(),
            'can' => ['create' => Gate::allows('create', MongoRole::class)],
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
