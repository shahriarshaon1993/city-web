<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { store, update } from '@/routes/admin/users';
import type { UserRow } from '@/types';

// CRUD-01: users are a dialog entity. One dialog invites (user = null) or edits.
const props = defineProps<{
    user: UserRow | null;
    roles: string[];
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ name: '', email: '', phone: '', role: '' });
const editing = computed(() => props.user !== null);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({
            name: props.user?.name ?? '',
            email: props.user?.email ?? '',
            phone: props.user?.phone ?? '',
            role: props.user?.role ?? '',
        });
        form.reset();
    }
});

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    if (props.user) {
        form.patch(update.url(props.user.id), options);
    } else {
        form.post(store.url(), options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        editing ? 'Edit user' : 'Invite a user'
                    }}</DialogTitle>
                    <DialogDescription>
                        <template v-if="editing">
                            Change their name, phone or role. The role decides
                            which sections they can open.
                        </template>
                        <template v-else>
                            They receive an email with a link, valid for 72
                            hours, to choose their password.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="user-name">
                        Name
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Input
                        id="user-name"
                        v-model="form.name"
                        required
                        maxlength="120"
                        autocomplete="off"
                        :aria-invalid="!!form.errors.name || undefined"
                    />
                    <p class="text-xs text-muted-foreground tabular-nums">
                        {{ form.name.length }} / 120
                    </p>
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-email">
                        Email address
                        <span
                            v-if="!editing"
                            class="text-destructive"
                            aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Input
                        id="user-email"
                        v-model="form.email"
                        type="email"
                        :required="!editing"
                        :readonly="editing"
                        maxlength="190"
                        autocomplete="off"
                        :aria-invalid="!!form.errors.email || undefined"
                        :aria-describedby="
                            editing ? 'user-email-help' : undefined
                        "
                    />
                    <p
                        v-if="editing"
                        id="user-email-help"
                        class="text-xs text-muted-foreground"
                    >
                        People change their own email address on their profile.
                    </p>
                    <InputError :message="form.errors.email" />
                </div>

                <div v-if="editing" class="grid gap-2">
                    <Label for="user-phone">Phone</Label>
                    <Input
                        id="user-phone"
                        v-model="form.phone"
                        type="tel"
                        inputmode="tel"
                        maxlength="20"
                        placeholder="+880 1712-345678"
                        :aria-invalid="!!form.errors.phone || undefined"
                    />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-role">
                        Role
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Select v-model="form.role" required>
                        <SelectTrigger
                            id="user-role"
                            class="w-full"
                            :aria-invalid="!!form.errors.role || undefined"
                        >
                            <SelectValue placeholder="Choose a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                                >{{ role }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="
                            form.processing ||
                            form.role === '' ||
                            form.name.trim() === ''
                        "
                    >
                        <Spinner v-if="form.processing" />
                        {{ editing ? 'Save changes' : 'Send invitation' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
