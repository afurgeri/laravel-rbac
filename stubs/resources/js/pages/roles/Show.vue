<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as rolesIndex } from '@/routes/roles';
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
            title: t('View :name', { name: role.name }),
        },
    ],
});
</script>

<template>
    <Head :title="t('View :name', { name: role.name })" />

    <CrudFormPage
        :schema="crud"
        :back-href="rolesIndex()"
        :title="t('View :name', { name: role.name })"
        :description="t('Review the role details and assigned permissions.')"
        submit-label=""
        :initial-values="role"
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
