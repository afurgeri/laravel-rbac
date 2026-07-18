<script setup lang="ts">
import InputError from '@/components/InputError.vue';

type Role = {
    id: string | number;
    name: string;
};

withDefaults(
    defineProps<{
        roles: Role[];
        selectedIds?: Array<string | number>;
        error?: string;
    }>(),
    {
        selectedIds: () => [],
        error: undefined,
    },
);
</script>

<template>
    <fieldset class="space-y-2">
        <legend class="text-sm font-medium">Roles</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            <label
                v-for="role in roles"
                :key="role.id"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <input
                    type="checkbox"
                    name="roles[]"
                    :value="role.id"
                    :checked="selectedIds.includes(role.id)"
                    class="size-4 rounded border-sidebar-border"
                />
                <span>{{ role.name }}</span>
            </label>
        </div>
        <InputError :message="error" />
    </fieldset>
</template>
