<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { useTranslation } from '@/composables/useTranslation';
import type { RolePermission } from '@/types/roles';

type Permission = RolePermission;
type Column = NonNullable<RolePermission['column']>;

const props = withDefaults(
    defineProps<{
        permissions: Permission[];
        selectedIds?: Array<string | number>;
        error?: string;
    }>(),
    {
        selectedIds: () => [],
        error: undefined,
    },
);

const { t } = useTranslation();

const columns = computed<{ key: Column; label: string }[]>(() => [
    { key: 'view', label: t('View') },
    { key: 'create', label: t('Create') },
    { key: 'update', label: t('Edit') },
    { key: 'delete', label: t('Delete') },
]);

/**
 * One row per subject: the usual actions in their column, anything else listed under the name.
 */
const rows = computed(() => {
    const bySubject = new Map<
        string,
        {
            subject: string;
            cells: Partial<Record<Column, Permission>>;
            others: Permission[];
        }
    >();

    for (const permission of props.permissions) {
        const row = bySubject.get(permission.subject) ?? {
            subject: permission.subject,
            cells: {},
            others: [],
        };

        if (permission.column) {
            row.cells[permission.column] = permission;
        } else {
            row.others.push(permission);
        }

        bySubject.set(permission.subject, row);
    }

    return [...bySubject.values()];
});

function isSelected(permission: Permission): boolean {
    return props.selectedIds.some((id) => String(id) === String(permission.id));
}
</script>

<template>
    <fieldset class="space-y-2">
        <legend class="text-sm font-medium">{{ t('Permissions') }}</legend>
        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-xs text-muted-foreground">
                    <tr>
                        <th scope="col" class="px-3 py-2 text-left font-medium">
                            <span class="sr-only">{{ t('Permissions') }}</span>
                        </th>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="w-12 px-1 py-2 text-center font-medium sm:w-16"
                        >
                            {{ column.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="row in rows" :key="row.subject">
                        <th scope="row" class="px-3 py-2 text-left align-top">
                            <span class="font-medium">{{ row.subject }}</span>
                            <span
                                v-if="row.others.length"
                                class="mt-1 flex flex-wrap gap-x-4 gap-y-1"
                            >
                                <label
                                    v-for="permission in row.others"
                                    :key="permission.id"
                                    class="flex items-center gap-1.5 text-xs font-normal text-muted-foreground"
                                    :title="permission.name"
                                >
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        :value="permission.id"
                                        :checked="isSelected(permission)"
                                        class="size-4 rounded border-sidebar-border"
                                    />
                                    {{ permission.action }}
                                </label>
                            </span>
                        </th>
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-1 py-2 text-center align-top"
                        >
                            <label
                                v-if="row.cells[column.key]"
                                class="inline-flex size-8 cursor-pointer items-center justify-center"
                                :title="row.cells[column.key]!.name"
                            >
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    :value="row.cells[column.key]!.id"
                                    :checked="
                                        isSelected(row.cells[column.key]!)
                                    "
                                    :aria-label="row.cells[column.key]!.label"
                                    class="size-4 rounded border-sidebar-border"
                                />
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <InputError :message="error" />
    </fieldset>
</template>
