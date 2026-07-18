<script setup lang="ts">
import InputError from '@/components/InputError.vue';

type Permission = {
    id: string | number;
    name: string;
};

withDefaults(
    defineProps<{
        permissions: Permission[];
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
        <legend class="text-sm font-medium">Permissions</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            <label
                v-for="permission in permissions"
                :key="permission.id"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <input
                    type="checkbox"
                    name="permissions[]"
                    :value="permission.id"
                    :checked="selectedIds.includes(permission.id)"
                    class="size-4 rounded border-sidebar-border"
                />
                <span>{{ permission.name }}</span>
            </label>
        </div>
        <InputError :message="error" />
    </fieldset>
</template>
