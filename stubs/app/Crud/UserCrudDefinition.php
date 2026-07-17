<?php

namespace App\Crud;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Crud\Concerns\AuthorizesViaGate;
use Modules\Crud\Contracts\AuthorizesCrudIndex;
use Modules\Crud\Contracts\AuthorizesCrudMutations;
use Modules\Crud\Contracts\EagerLoadsCrudRelations;
use Modules\Crud\Contracts\HasCrudFilters;
use Modules\Crud\Contracts\HasDefaultCrudSort;
use Modules\Crud\CrudColumn;
use Modules\Crud\CrudDefinition;
use Modules\Crud\CrudField;
use Modules\Crud\CrudFilter;
use Modules\Rbac\Models\Role;

class UserCrudDefinition implements AuthorizesCrudIndex, AuthorizesCrudMutations, CrudDefinition, EagerLoadsCrudRelations, HasCrudFilters, HasDefaultCrudSort
{
    use AuthorizesViaGate;

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        return User::class;
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
            CrudColumn::make('id')->sortable(),
            CrudColumn::make('name')->sortable()->searchable(),
            CrudColumn::make('email')->sortable()->searchable(),
        ];
    }

    public function fields(): array
    {
        return [
            CrudField::make('name', ['required', 'string', 'max:255']),
            CrudField::make('email', ['required', 'email', 'max:255'])->unique()->email(),
            CrudField::make('password', ['required', 'string', 'min:8'])->createOnly()->password()->confirmed(),
        ];
    }

    public function eagerLoads(): array
    {
        return ['roles:id,name'];
    }

    public function defaultSortColumn(): string
    {
        return 'name';
    }

    public function defaultSortDirection(): string
    {
        return 'asc';
    }

    public function filters(): array
    {
        return [
            CrudFilter::make('role')
                ->select(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                ->relation('roles', 'id')
                ->clearable(),
            CrudFilter::make('created_from', 'created_at')->date()->operator('>=')->range('created_at')
                ->default(fn (): string => now()->startOfMonth()->toDateString())
                ->maxDate(fn (): string => now()->toDateString()),
            CrudFilter::make('created_to', 'created_at')->date()->operator('<=')->range('created_at')
                ->default(fn (): string => now()->toDateString())
                ->maxDate(fn (): string => now()->toDateString()),
        ];
    }
}
