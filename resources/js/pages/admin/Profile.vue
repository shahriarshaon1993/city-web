<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import DisplaySection from '@/components/admin/profile/DisplaySection.vue';
import SessionsSection from '@/components/admin/profile/SessionsSection.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { UserSession } from '@/types';

defineProps<{
    passwordRules: string;
    sessions: UserSession[];
}>();

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Your profile" />

    <AdminPageHeader
        title="Your profile"
        description="Your details, password, signed-in devices and how the dashboard looks for you."
    />

    <div class="flex max-w-[1100px] flex-col gap-6">
        <AdminSection
            title="Your details"
            description="Shown to your colleagues next to your changes. If you change your email address, we send a link to confirm the new one."
        >
            <Form
                v-bind="ProfileController.update.form()"
                :options="{ preserveScroll: true }"
                class="grid max-w-xl gap-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name"
                        >Name
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        ></Label
                    >
                    <Input
                        id="name"
                        name="name"
                        :default-value="user.name"
                        required
                        maxlength="120"
                        autocomplete="name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email"
                        >Email address
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        ></Label
                    >
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        :default-value="user.email"
                        required
                        maxlength="190"
                        autocomplete="username"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Phone</Label>
                    <Input
                        id="phone"
                        name="phone"
                        type="tel"
                        inputmode="tel"
                        :default-value="user.phone ?? ''"
                        maxlength="20"
                        autocomplete="tel"
                        placeholder="+880 1712-345678"
                    />
                    <InputError :message="errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">{{
                        user.roles.length > 1 ? 'Roles' : 'Role'
                    }}</Label>
                    <Input
                        id="role"
                        :model-value="user.roles.join(', ')"
                        readonly
                        aria-describedby="role-help"
                    />
                    <p id="role-help" class="text-xs text-muted-foreground">
                        An administrator sets your role; it decides which
                        sections you can open.
                    </p>
                </div>

                <p class="text-xs text-muted-foreground">
                    Profile pictures arrive with the media library (phase 11.2);
                    until then your initials are shown.
                </p>

                <div>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Save details
                    </Button>
                </div>
            </Form>
        </AdminSection>

        <AdminSection
            title="Password"
            description="Use at least 8 characters with upper and lower case letters and a number. Passwords found in known data breaches are refused."
        >
            <Form
                v-bind="ProfileController.updatePassword.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="grid max-w-xl gap-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="current_password">Current password</Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">New password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        required
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation"
                        >Repeat the new password</Label
                    >
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        :passwordrules="passwordRules"
                        required
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div>
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" />
                        Change password
                    </Button>
                </div>
            </Form>
        </AdminSection>

        <SessionsSection :sessions="sessions" />

        <DisplaySection />
    </div>
</template>
