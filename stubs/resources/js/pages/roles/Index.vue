<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CrudPage from '@/components/crud/CrudPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { useTranslation } from '@/composables/useTranslation';
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

const { t } = useTranslation();

setLayoutProps({
    breadcrumbs: [
        {
            title: t('Roles'),
            href: rolesIndex(),
        },
    ],
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
            label: t('Create :name', { name: t('Role') }),
            title: t('Create :name', { name: t('Role') }),
            description: t('Add a new role and choose its permissions.'),
        }"
        :edit="{
             action: (record) => updateRole.form.patch(String(record.id)),
             href: (record) => editRole(String(record.id)),
            can: (record) => record.can.update,
            title: (record) => t('Edit :name', { name: record.name }),
            description: t('Update the role details and assigned permissions.'),
        }"
        :show="{
             href: (record) => showRole(String(record.id)),
            can: (record) => record.can.show,
            title: (record) => t('View :name', { name: record.name }),
        }"
        :destroy="{
             action: (record) => destroyRole.form.delete(String(record.id)),
            can: (record) => record.can.delete,
            title: (record) => t('Delete :name?', { name: record.name }),
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
