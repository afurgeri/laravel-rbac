<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import RolePermissionsField from '@/pages/roles/RolePermissionsField.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as rolesIndex, store as storeRole } from '@/routes/roles';
import type { CrudSchema } from '@/types/crud';

defineProps<{
    crud: CrudSchema;
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
            title: t('Create :name', { name: t('Role') }),
        },
    ],
});
</script>

<template>
    <Head :title="t('Create :name', { name: t('Role') })" />

    <CrudFormPage
        :schema="crud"
        :action="storeRole.form()"
        :back-href="rolesIndex()"
        :title="t('Create :name', { name: t('Role') })"
        :description="t('Add a new role and choose its permissions.')"
        :submit-label="t('Create :name', { name: t('Role') })"
    >
        <template #fields="{ errors }">
            <RolePermissionsField
                :permissions="permissions"
                :error="errors.permissions"
            />
        </template>
    </CrudFormPage>
</template>
