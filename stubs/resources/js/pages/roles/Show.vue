<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { index as rolesIndex } from '@/routes/roles';
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
                title: 'View role',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`View ${role.name}`" />

    <CrudFormPage
        :schema="crud"
        :back-href="rolesIndex()"
        :title="`View ${role.name}`"
        description="Review the role details and assigned permissions."
        submit-label=""
        :defaults="role"
        :read-only="true"
    >
        <template #fields="{ readOnly }">
            <RolePermissionsField
                :permissions="permissions"
                :selected-ids="role.permission_ids"
                :read-only="readOnly"
            />
        </template>
    </CrudFormPage>
</template>
