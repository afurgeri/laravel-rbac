<?php

namespace Modules\Rbac;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Lists the stored permissions with a readable label, grouped by what they cover
 * and in the usual action order, ready for the role screens.
 */
final class PermissionCatalog
{
    /**
     * The actions that get a column of their own in the permissions table, in display order.
     *
     * @var list<'view'|'create'|'update'|'delete'>
     */
    public const COLUMNS = ['view', 'create', 'update', 'delete'];

    /**
     * @return list<array{id: int|string, name: string, label: string, subject: string, column: 'view'|'create'|'update'|'delete'|null, action: string}>
     */
    public function all(): array
    {
        $permissionModel = RbacModels::permission();

        $permissions = $permissionModel::query()
            ->get()
            ->sort(fn (Model $first, Model $second): int => $this->order($this->nameOf($first)) <=> $this->order($this->nameOf($second)));

        $catalog = [];

        foreach ($permissions as $permission) {
            $catalog[] = $this->describe($permission);
        }

        return $catalog;
    }

    /**
     * @return array{id: int|string, name: string, label: string, subject: string, column: 'view'|'create'|'update'|'delete'|null, action: string}
     */
    public function describe(Model $permission): array
    {
        $name = $this->nameOf($permission);
        $subject = $this->subject($name);
        [$column, $action] = $this->action($name);
        $key = $permission->getKey();

        return [
            'id' => is_int($key) ? $key : (string) $key,
            'name' => $name,
            'label' => $action === null || $subject === $name ? $name : $subject.': '.Str::lower($action),
            'subject' => $subject,
            'column' => $column,
            'action' => $action ?? $name,
        ];
    }

    /**
     * What the permission covers, using the configured subject labels.
     */
    public function subject(string $name): string
    {
        $subjects = (array) config('rbac.permission_subjects', []);
        $label = $subjects[Str::beforeLast($name, '.')] ?? null;

        return is_string($label) ? __($label) : $name;
    }

    /**
     * The column the permission belongs to, if any, and how to name its action.
     *
     * @return array{0: 'view'|'create'|'update'|'delete'|null, 1: string|null}
     */
    public function action(string $name): array
    {
        $actions = (array) config('rbac.permission_actions', []);
        $verb = Str::afterLast($name, '.');
        $label = $actions[$name] ?? null;

        if (is_string($label)) {
            return [null, __($label)];
        }

        return match ($verb) {
            'view' => ['view', __('View')],
            'create' => ['create', __('Create')],
            'update' => ['update', __('Edit')],
            'delete' => ['delete', __('Delete')],
            default => [null, is_string($actions[$verb] ?? null) ? __($actions[$verb]) : null],
        };
    }

    /**
     * Sorts by what the permission covers, then by the usual action order.
     *
     * @return array{0: string, 1: int}
     */
    private function order(string $name): array
    {
        $actionOrder = array_flip([...self::COLUMNS, ...array_keys((array) config('rbac.permission_actions', []))]);

        return [
            Str::lower($this->subject($name)),
            $actionOrder[Str::afterLast($name, '.')] ?? count($actionOrder),
        ];
    }

    private function nameOf(Model $permission): string
    {
        return (string) $permission->getAttribute('name');
    }
}
