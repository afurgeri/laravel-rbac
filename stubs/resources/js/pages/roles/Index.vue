<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudPage from '@/components/crud/CrudPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import {
    create as createRole,
    destroy as destroyRole,
    edit as editRole,
    index as rolesIndex,
    show as showRole,
    store as storeRole,
    update as updateRole,
} from '@/routes/roles';
import type { CrudPaginator, CrudRecord, CrudSchema } from '@/types/crud';

type Role = CrudRecord & {
    id: string | number;
    name: string;
    permission_ids: Array<string | number>;
    can: {
        show: boolean;
        update: boolean;
        delete: boolean;
    };
};

type Permission = {
    id: string | number;
    name: string;
};

defineProps<{
    crud: CrudSchema;
    roles: CrudPaginator<Role>;
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
        :records="roles"
        :create="{
            can: can.create,
            href: createRole(),
            action: storeRole.form(),
            label: 'Create role',
            title: 'Create role',
            description: 'Add a new role and choose its permissions.',
        }"
        :edit="{
            action: (record) => updateRole.form.patch(record.id),
            href: (record) => editRole(record.id),
            can: (record) => record.can.update,
            title: (record) => `Edit ${record.name}`,
            description: 'Update the role details and assigned permissions.',
        }"
        :show="{
            href: (record) => showRole(record.id),
            can: (record) => record.can.show,
            title: (record) => `View ${record.name}`,
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
