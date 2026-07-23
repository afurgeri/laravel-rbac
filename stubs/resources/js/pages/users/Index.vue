<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CrudPage from '@/components/crud/CrudPage.vue';
import UserPasswordDialog from '@/pages/users/UserPasswordDialog.vue';
import UserRolesField from '@/pages/users/UserRolesField.vue';
import {
    create as createUser,
    destroy as destroyUser,
    edit as editUser,
    index as usersIndex,
    show as showUser,
    store as storeUser,
    update as updateUser,
} from '@/routes/users';
import type { CrudPaginator, CrudRecord, CrudSchema } from '@/types/crud';

type User = CrudRecord & {
    id: string | number;
    name: string;
    email: string;
    role_ids: Array<string | number>;
    can: {
        show: boolean;
        update: boolean;
        delete: boolean;
    };
};

type Role = {
    id: string | number;
    name: string;
};

defineProps<{
    crud: CrudSchema;
    users: CrudPaginator<User>;
    roles: Role[];
    can: {
        create: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: usersIndex(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="crud.title" />

    <CrudPage
        :schema="crud"
        :records="users"
        :create="{
            can: can.create,
            href: createUser(),
            action: storeUser.form(),
            label: 'Create user',
            title: 'Create user',
            description: 'Add a new user and choose their roles.',
        }"
        :edit="{
            action: (record) => updateUser.form.patch(record.id),
            href: (record) => editUser(record.id),
            can: (record) => record.can.update,
            title: (record) => `Edit ${record.name}`,
            description: 'Update the user details and assigned roles.',
        }"
        :show="{
            href: (record) => showUser(record.id),
            can: (record) => record.can.show,
            title: (record) => `View ${record.name}`,
        }"
        :destroy="{
            action: (record) => destroyUser.form.delete(record.id),
            can: (record) => record.can.delete,
            title: (record) => `Delete ${record.name}?`,
        }"
    >
        <template #cell-name="{ value }">
            <span class="font-medium">{{ value }}</span>
        </template>

        <template #actions-before="{ record }">
            <UserPasswordDialog v-if="record.can.update" :user="record" />
        </template>

        <template #create-fields="{ errors }">
            <UserRolesField :roles="roles" :error="errors.roles" />
        </template>

        <template #edit-fields="{ record, errors }">
            <UserRolesField
                :roles="roles"
                :selected-ids="record.role_ids"
                :error="errors.roles"
            />
        </template>
    </CrudPage>
</template>
