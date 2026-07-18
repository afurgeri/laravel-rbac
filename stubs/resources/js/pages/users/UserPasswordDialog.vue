<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import password from '@/routes/users/password';

const props = defineProps<{
    user: {
        id: string | number;
        name: string;
    };
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button type="button" variant="secondary" size="sm">
                Change password
            </Button>
        </DialogTrigger>

        <DialogContent class="sm:max-w-lg">
            <DialogHeader class="text-left">
                <DialogTitle>Change {{ props.user.name }} password</DialogTitle>
                <DialogDescription>
                    Set a new password for this user. They can change it later
                    from their security settings.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="password.update.form.patch(props.user.id)"
                v-slot="{ errors, processing }"
                reset-on-success
                class="flex flex-col gap-4 px-1 pb-6"
            >
                <div class="space-y-2">
                    <Label :for="`user-${props.user.id}-password`">
                        New password
                    </Label>
                    <Input
                        :id="`user-${props.user.id}-password`"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                        :aria-invalid="errors.password ? 'true' : undefined"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="space-y-2">
                    <Label :for="`user-${props.user.id}-password-confirmation`">
                        Confirm password
                    </Label>
                    <Input
                        :id="`user-${props.user.id}-password-confirmation`"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        :aria-invalid="
                            errors.password_confirmation ? 'true' : undefined
                        "
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button type="submit" :disabled="processing">
                    Change password
                </Button>
            </Form>
        </DialogContent>
    </Dialog>
</template>
