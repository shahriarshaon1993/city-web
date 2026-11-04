<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Laptop } from '@lucide/vue';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import AdminSection from '@/components/admin/AdminSection.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import type { UserSession } from '@/types';

const props = defineProps<{ sessions: UserSession[] }>();

const open = ref(false);
const others = computed(() =>
    props.sessions.filter((session) => !session.current),
);
</script>

<template>
    <AdminSection
        title="Signed-in devices"
        description="Every browser that is signed in to your account. Sessions end by themselves after 120 minutes without activity."
    >
        <ul class="divide-y border-y">
            <li
                v-for="session in sessions"
                :key="session.id"
                class="flex items-center gap-4 py-3"
            >
                <Laptop
                    class="size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">
                        {{ session.device }}
                        <span
                            v-if="session.current"
                            class="ml-2 text-overline tracking-[0.16em] text-success uppercase"
                            >This device</span
                        >
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ session.ip ?? 'Unknown address' }} · active
                        <time
                            :datetime="session.last_active"
                            :title="formatDateTime(session.last_active)"
                            >{{ timeAgo(session.last_active) }}</time
                        >
                    </p>
                </div>
            </li>
        </ul>

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <Button
                variant="outline"
                :disabled="others.length === 0"
                @click="open = true"
            >
                Sign out other sessions
            </Button>
            <p v-if="others.length === 0" class="text-xs text-muted-foreground">
                No other device is signed in.
            </p>
        </div>

        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-md">
                <Form
                    v-bind="ProfileController.destroyOtherSessions.form()"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    class="grid gap-5"
                    v-slot="{ errors, processing }"
                    @success="open = false"
                >
                    <DialogHeader>
                        <DialogTitle>
                            Sign out {{ others.length }} other
                            {{ others.length === 1 ? 'session' : 'sessions' }}?
                        </DialogTitle>
                        <DialogDescription>
                            Those browsers will have to sign in again. This
                            device stays signed in. Enter your password to
                            confirm.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="sessions_password">Your password</Label>
                        <PasswordInput
                            id="sessions_password"
                            name="current_password"
                            autocomplete="current-password"
                            required
                            autofocus
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            @click="open = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            Sign out other sessions
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </AdminSection>
</template>
