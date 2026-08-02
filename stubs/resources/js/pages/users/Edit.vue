<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CrudFormPage from '@/components/crud/CrudFormPage.vue';
import UserRolesField from '@/pages/users/UserRolesField.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as usersIndex, update as updateUser } from '@/routes/users';
import type { CrudSchema } from '@/types/crud';

const { user } = defineProps<{
    crud: CrudSchema;
    user: {
        id: string | number;
        name: string;
        email: string;
        role_ids: (string | number)[];
    };
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
            title: t('Edit :name', { name: user.name }),
        },
    ],
});
</script>

<template>
    <Head :title="t('Edit :name', { name: user.name })" />

    <CrudFormPage
        :schema="crud"
         :action="updateUser.form.patch(String(user.id))"
        :back-href="usersIndex()"
        :title="t('Edit :name', { name: user.name })"
        :description="t('Update the user details and assigned roles.')"
        :submit-label="t('Save changes')"
        :initial-values="user"
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
