<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { index as rolesIndex, update as updateRole } from '@/routes/roles';
import type { CrudSchema } from '@/types/crud';

defineProps<{
    crud: CrudSchema;
    role: {
        id: string | number;
        name: string;
        permission_ids: (string | number)[];
    };
    permissions: { id: string | number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Roles',
                href: rolesIndex(),
            },
            {
                title: 'Edit role',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${role.name}`" />

    <CrudFormPage
        :schema="crud"
        :action="updateRole.form.patch(role.id)"
        :back-href="rolesIndex()"
        :title="`Edit ${role.name}`"
        description="Update the role details and assigned permissions."
        submit-label="Save changes"
        :defaults="role"
    >
        <template #fields="{ errors }">
            <RolePermissionsField
                :permissions="permissions"
                :selected-ids="role.permission_ids"
                :error="errors.permissions"
            />
        </template>
    </CrudFormPage>
</template>
