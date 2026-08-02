<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as rolesIndex, update as updateRole } from '@/routes/roles';
import type { CrudSchema } from '@/types/crud';

const { role } = defineProps<{
    crud: CrudSchema;
    role: {
        id: string | number;
        name: string;
        permission_ids: (string | number)[];
    };
    permissions: { id: string | number; name: string }[];
}>();

const { t } = useTranslation();

setLayoutProps({
    breadcrumbs: [
        {
            title: t('Roles'),
            href: rolesIndex(),
        },
        {
            title: t('Edit :name', { name: role.name }),
        },
    ],
});
</script>

<template>
    <Head :title="t('Edit :name', { name: role.name })" />

    <CrudFormPage
        :schema="crud"
         :action="updateRole.form.patch(String(role.id))"
        :back-href="rolesIndex()"
        :title="t('Edit :name', { name: role.name })"
        :description="t('Update the role details and assigned permissions.')"
        :submit-label="t('Save changes')"
        :initial-values="role"
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
