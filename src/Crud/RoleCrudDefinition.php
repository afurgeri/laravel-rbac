<?php

namespace Modules\Rbac\Crud;

use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Concerns\AuthorizesViaGate;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudFormMode;
use Modules\Crud\Contracts\HasCrudOperations;
use Modules\Crud\Contracts\HasDefaultCrudPageSize;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFormMode;
use Modules\Rbac\RbacModels;

class RoleCrudDefinition implements AuthorizesCrudIndex, AuthorizesCrudMutations, CrudDefinition, EagerLoadsCrudRelations, HasCrudFormMode, HasCrudOperations, HasDefaultCrudPageSize, HasDefaultCrudSort
{
    use AuthorizesViaGate;

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return RbacModels::role();
    }

    public function formMode(): CrudFormMode
    {
        return CrudFormMode::Page;
    }

    public function disabledOperations(): array
    {
        return [];
    }

    public function title(): string
    {
        return __('Roles');
    }

    public function description(): ?string
    {
        return __('Manage application roles and their access rules.');
    }

    public function emptyLabel(): ?string
    {
        return __('No roles found.');
    }

    public function columns(): array
    {
        return [
            CrudColumn::make('id')->sortable(),
            CrudColumn::make('name')->sortable()->searchable(),
            CrudColumn::make('permission_ids')->computed(),
        ];
    }

    public function fields(): array
    {
        return [
            CrudField::make('name', ['required', 'string', 'max:255'])->unique(),
        ];
    }

    public function eagerLoads(): array
    {
        return config('rbac.storage', 'mysql') === 'mongodb'
            ? ['permissions:id,name,role_ids']
            : ['permissions:id,name'];
    }

    public function defaultSortColumn(): string
    {
        return 'name';
    }

    public function defaultSortDirection(): string
    {
        return 'asc';
    }

    public function defaultPageSize(): int
    {
        return 10;
    }
}
