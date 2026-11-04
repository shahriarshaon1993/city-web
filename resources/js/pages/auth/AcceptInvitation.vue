<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Set your password',
        description: 'Choose a password to finish setting up your account',
    },
});

const props = defineProps<{
    email: string;
    name: string;
    submitUrl: string;
    passwordRules: string;
}>();

const formAttrs = computed(() => ({
    action: props.submitUrl,
    method: 'post' as const,
}));
</script>

<template>
    <Head title="Set your password" />

    <Form
        v-bind="formAttrs"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    autocomplete="username"
                    :model-value="email"
                    class="mt-1 block w-full"
                    readonly
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    autofocus
                    placeholder="At least 8 characters"
                    :passwordrules="passwordRules"
                />
                <p class="text-sm text-muted-foreground">
                    8+ characters with upper and lower case letters and a
                    number.
                </p>
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="accept-invitation-button"
            >
                <Spinner v-if="processing" />
                Save password
            </Button>
        </div>
    </Form>
</template>
