<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useTranslation } from '@/composables/useTranslation';
import type { RolePermission } from '@/types/roles';

type Permission = RolePermission;

const props = defineProps<{
    permissionIds: Array<string | number>;
    permissions: Permission[];
    roleName: string;
}>();

const { t } = useTranslation();
const previewLimit = 3;

const selectedPermissions = computed(() => {
    const permissionsById = new Map(
        props.permissions.map((permission) => [
            String(permission.id),
            permission,
        ]),
    );

    return props.permissionIds
        .map((id) => permissionsById.get(String(id)))
        .filter(
            (permission): permission is Permission => permission !== undefined,
        );
});

const previewPermissions = computed(() =>
    selectedPermissions.value.slice(0, previewLimit),
);

const remainingCount = computed(
    () => selectedPermissions.value.length - previewPermissions.value.length,
);
</script>

<template>
    <span
        v-if="selectedPermissions.length === 0"
        class="text-sm text-muted-foreground"
    >
        {{ t('No permissions') }}
    </span>

    <div v-else class="flex max-w-[24rem] flex-wrap items-center gap-1">
        <Badge
            v-for="permission in previewPermissions"
            :key="permission.id"
            variant="secondary"
            class="max-w-44 truncate"
            :title="permission.name"
        >
            {{ permission.label }}
        </Badge>

        <Dialog v-if="remainingCount > 0">
            <DialogTrigger as-child>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-6 rounded-full px-2 text-xs"
                >
                    +{{ remainingCount }}
                </Button>
            </DialogTrigger>

            <DialogScrollContent class="sm:max-w-xl">
                <DialogHeader class="text-left">
                    <DialogTitle>
                        {{ t('Permissions for :name', { name: roleName }) }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            t(':count permissions assigned.', {
                                count: selectedPermissions.length,
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="flex max-h-[min(60vh,32rem)] flex-wrap gap-2 overflow-y-auto pr-2"
                >
                    <Badge
                        v-for="permission in selectedPermissions"
                        :key="permission.id"
                        variant="outline"
                    >
                        {{ permission.label }}
                    </Badge>
                </div>
            </DialogScrollContent>
        </Dialog>
    </div>
</template>
