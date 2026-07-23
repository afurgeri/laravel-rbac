<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import UserRolesField from '@/pages/users/UserRolesField.vue';
import { index as usersIndex, update as updateUser } from '@/routes/users';
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
                title: 'Edit user',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <CrudFormPage
        :schema="crud"
         :action="updateUser.form.patch(String(user.id))"
        :back-href="usersIndex()"
        :title="`Edit ${user.name}`"
        description="Update the user details and assigned roles."
        submit-label="Save changes"
        :defaults="user"
        :fields="crud.fields.filter((field) => field.visible_on_update)"
    >
        <template #fields="{ errors }">
            <UserRolesField
                :roles="roles"
                :selected-ids="user.role_ids"
                :error="errors.roles"
            />
        </template>
    </CrudFormPage>
</template>
