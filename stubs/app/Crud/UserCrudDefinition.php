<?php

namespace App\Crud;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Concerns\AuthorizesViaGate;
use Modules\Crud\Concerns\HasDefaultCrudPresentation;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudExport;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasCrudOperations;
use Modules\Crud\Contracts\HasCrudPresentation;
use Modules\Crud\Contracts\HasDefaultCrudPageSize;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudExportColumn;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;
use Modules\Rbac\Models\Role;

class UserCrudDefinition implements AuthorizesCrudIndex, AuthorizesCrudMutations, CrudDefinition, EagerLoadsCrudRelations, HasCrudExport, HasCrudFilters, HasCrudOperations, HasCrudPresentation, HasDefaultCrudPageSize, HasDefaultCrudSort
{
    use AuthorizesViaGate, HasDefaultCrudPresentation;

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return User::class;
    }

    public function disabledOperations(): array
    {
        return [];
    }

    public function title(): string
    {
        return __('Users');
    }

    public function description(): ?string
    {
        return __('Manage application users and their assigned roles.');
    }

    public function emptyLabel(): ?string
    {
        return __('No users found.');
    }

    public function columns(): array
    {
        return [
            CrudColumn::make('id')->hidden(),
            CrudColumn::make('name')->sortable()->searchable(),
            CrudColumn::make('email')->sortable()->searchable(),
            CrudColumn::make('roles'),
            CrudColumn::make('is_active')->sortable()->label(__('Active')),
        ];
    }

    public function fields(): array
    {
        return [
            CrudField::make('name', ['required', 'string', 'max:255']),
            CrudField::make('email', ['required', 'email', 'max:255'])->unique()->email(),
            CrudField::make('password', ['required', 'string', 'min:8'])->createOnly()->password()->confirmed(),
            CrudField::make('is_active', ['required', 'boolean'])->checkbox()->default(true)->label(__('Active')),
        ];
    }

    public function eagerLoads(): array
    {
        return ['roles:id,name'];
    }

    public function exportColumns(): array
    {
        return [
            CrudExportColumn::make('id')->header('ID'),
            CrudExportColumn::make('name')->header('Name'),
            CrudExportColumn::make('email')->header('Email'),
            CrudExportColumn::make('is_active')->header('Active')
                ->valueUsing(fn (Model $record): string => $record->getAttribute('is_active') ? __('Yes') : __('No')),
            CrudExportColumn::make('roles')->header('Roles')
                ->valueUsing(fn (Model $record): string => $record->getRelation('roles')->pluck('name')->implode(', ')),
        ];
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

    public function filters(): array
    {
        return [
            CrudFilter::make('role')
                ->select(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                ->relation('roles', 'id')
                ->clearable(),
            CrudFilter::make('is_active')
                ->select(['1' => __('Active'), '0' => __('Inactive')])
                ->clearable()
                ->label(__('Status')),
        ];
    }
}
