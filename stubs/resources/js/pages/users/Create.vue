<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import UserRolesField from '@/pages/users/UserRolesField.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as usersIndex, store as storeUser } from '@/routes/users';
import type { CrudSchema } from '@/types/crud';

defineProps<{
    crud: CrudSchema;
    roles: { id: string | number; name: string }[];
}>();

const { t } = useTranslation();

setLayoutProps({
    breadcrumbs: [
        {
            title: t('Users'),
            href: usersIndex(),
        },
        {
            title: t('Create :name', { name: t('User') }),
        },
    ],
});
</script>

<template>
    <Head :title="t('Create :name', { name: t('User') })" />

    <CrudFormPage
        :schema="crud"
        :action="storeUser.form()"
        :back-href="usersIndex()"
        :title="t('Create :name', { name: t('User') })"
        :description="t('Add a new user and choose their roles.')"
        :submit-label="t('Create :name', { name: t('User') })"
    >
        <template #fields="{ errors }">
            <UserRolesField :roles="roles" :error="errors.roles" />
        </template>
    </CrudFormPage>
</template>
