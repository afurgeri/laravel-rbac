<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import UserRolesField from '@/pages/users/UserRolesField.vue';
import { index as usersIndex, store as storeUser } from '@/routes/users';
import type { CrudSchema } from '@/types/crud';

defineProps<{
    crud: CrudSchema;
    roles: { id: string | number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: usersIndex(),
            },
            {
                title: 'Create user',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Create ${crud.title}`" />

    <CrudFormPage
        :schema="crud"
        :action="storeUser.form()"
        :back-href="usersIndex()"
        title="Create user"
        description="Add a new user and choose their roles."
        submit-label="Create user"
    >
        <template #fields="{ errors }">
            <UserRolesField :roles="roles" :error="errors.roles" />
        </template>
    </CrudFormPage>
</template>
