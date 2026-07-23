<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import UserRolesField from '@/pages/users/UserRolesField.vue';
import { index as usersIndex } from '@/routes/users';
import type { CrudSchema } from '@/types/crud';

defineProps<{
    crud: CrudSchema;
    user: {
        id: string | number;
        name: string;
        email: string;
        role_ids: (string | number)[];
    };
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
                title: 'View user',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`View ${user.name}`" />

    <CrudFormPage
        :schema="crud"
        :back-href="usersIndex()"
        :title="`View ${user.name}`"
        description="Review the user details and assigned roles."
        submit-label=""
        :defaults="user"
        :fields="crud.fields.filter((field) => field.visible_on_update)"
        :read-only="true"
    >
        <template #fields="{ readOnly }">
            <UserRolesField
                :roles="roles"
                :selected-ids="user.role_ids"
                :read-only="readOnly"
            />
        </template>
    </CrudFormPage>
</template>
