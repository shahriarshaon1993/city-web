<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import DisplaySection from '@/components/admin/profile/DisplaySection.vue';
import SessionsSection from '@/components/admin/profile/SessionsSection.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/admin/profile';
import type { UserSession } from '@/types';

defineProps<{
    passwordRules: string;
    sessions: UserSession[];
    lastSaved: string | null;
}>();

const user = computed(() => usePage().props.auth.user);

// CRUD-04: the details form is the editor's form — Ctrl/⌘+S, the dirty marker, "Last saved" and
// the unsaved-changes guard all come from AdminEditor. Password, devices and display settings
// are separate actions with their own buttons.
const form = useForm({
    name: user.value.name,
    email: user.value.email,
    phone: user.value.phone ?? '',
});

function save(): void {
    form.patch(update.url(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Your profile" />

    <AdminEditor
        title="Your profile"
        description="Your details, password, signed-in devices and how the dashboard looks for you."
        :form="form"
        :last-saved="lastSaved"
        save-label="Save details"
        @save="save"
    >
        <template #rail>
            <p class="text-xs text-muted-foreground">
                Saves your name, email address and phone. Password, devices and
                display settings save on their own.
            </p>
        </template>

        <div class="flex max-w-[1100px] flex-col gap-6">
            <AdminSection
                title="Your details"
                description="Shown to your colleagues next to your changes. If you change your email address, we send a link to confirm the new one."
            >
                <form class="grid max-w-xl gap-5" @submit.prevent="save">
                    <div class="grid gap-2">
                        <Label for="name"
                            >Name
                            <span class="text-destructive" aria-hidden="true"
                                >*</span
                            ></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            name="name"
                            required
                            maxlength="120"
                            autocomplete="name"
                            :aria-invalid="!!form.errors.name || undefined"
                        />
                        <InputError :message="form.errors.name" />
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
                            v-model="form.email"
                            name="email"
                            type="email"
                            required
                            maxlength="190"
                            autocomplete="username"
                            :aria-invalid="!!form.errors.email || undefined"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input
                            id="phone"
                            v-model="form.phone"
                            name="phone"
                            type="tel"
                            inputmode="tel"
                            maxlength="20"
                            autocomplete="tel"
                            placeholder="+880 1712-345678"
                            :aria-invalid="!!form.errors.phone || undefined"
                        />
                        <InputError :message="form.errors.phone" />
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
                        Profile pictures arrive with the media library (phase
                        11.2); until then your initials are shown.
                    </p>
                </form>
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
    </AdminEditor>
</template>
