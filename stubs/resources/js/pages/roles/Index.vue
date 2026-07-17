<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudPage from '@/components/crud/CrudPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import {
    destroy as destroyRole,
    index as rolesIndex,
    store as storeRole,
    update as updateRole,
} from '@/routes/roles';
import type { CrudRecord, CrudSchema } from '@/types/crud';

type Role = CrudRecord & {
    id: number;
    name: string;
    permission_ids: number[];
    can: {
        update: boolean;
        delete: boolean;
    };
};

type Permission = {
    id: number;
    name: string;
};

type PaginatedRoles = {
    data: Role[];
    total: number;
};

defineProps<{
    crud: CrudSchema;
    roles: PaginatedRoles;
    permissions: Permission[];
    can: {
        create: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: rolesIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="crud.title" />

    <CrudPage
        :schema="crud"
        :records="roles.data"
        :create="{
            can: can.create,
            action: storeRole.form(),
            label: 'Create role',
            title: 'Create role',
            description: 'Add a new role and choose its permissions.',
        }"
        :edit="{
            action: (record) => updateRole.form.patch(record.id),
            can: (record) => record.can.update,
            title: (record) => `Edit ${record.name}`,
            description: 'Update the role details and assigned permissions.',
        }"
        :destroy="{
            action: (record) => destroyRole.form.delete(record.id),
            can: (record) => record.can.delete,
            title: (record) => `Delete ${record.name}?`,
        }"
    >
        <template #cell-name="{ value }">
            <span class="font-medium">{{ value }}</span>
        </template>

        <template #create-fields="{ errors }">
            <RolePermissionsField
                :permissions="permissions"
                :error="errors.permissions"
            />
        </template>

        <template #edit-fields="{ record, errors }">
            <RolePermissionsField
                :permissions="permissions"
                :selected-ids="record.permission_ids"
                :error="errors.permissions"
            />
        </template>
    </CrudPage>
</template>
