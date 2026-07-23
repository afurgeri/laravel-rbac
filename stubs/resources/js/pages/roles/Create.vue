<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { index as rolesIndex, store as storeRole } from '@/routes/roles';
import type { CrudSchema } from '@/types/crud';

defineProps<{
    crud: CrudSchema;
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
                title: 'Create role',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Create ${crud.title}`" />

    <CrudFormPage
        :schema="crud"
        :action="storeRole.form()"
        :back-href="rolesIndex()"
        title="Create role"
        description="Add a new role and choose its permissions."
        submit-label="Create role"
    >
        <template #fields="{ errors }">
            <RolePermissionsField
                :permissions="permissions"
                :error="errors.permissions"
            />
        </template>
    </CrudFormPage>
</template>
