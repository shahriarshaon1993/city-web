<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Bookmark, Star, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { destroy, store, update } from '@/routes/admin/table-views';
import type { TableViewItem, ViewState } from '@/types';

// ADM-TBL-010 saved views: pick one (the URL gets ?view={id}), save the current state under a
// name (personal, or shared when the user may), make one the default, overwrite or delete own views.
const props = defineProps<{
    scope: string;
    tableKey: string;
    views: TableViewItem[];
    activeView: TableViewItem | null;
    canShare: boolean;
    currentState: () => ViewState;
}>();

const emit = defineEmits<{ apply: [view: TableViewItem] }>();

const listOpen = ref(false);
const saveOpen = ref(false);
const deleting = ref<TableViewItem | null>(null);
const deleteOpen = ref(false);
const deleteProcessing = ref(false);

const form = useForm<{
    table_key: string;
    name: string;
    state: ViewState;
    is_shared: boolean;
    is_default: boolean;
}>({
    table_key: props.tableKey,
    name: '',
    state: {},
    is_shared: false,
    is_default: false,
});

function openSave(): void {
    form.reset();
    form.clearErrors();
    listOpen.value = false;
    saveOpen.value = true;
}

function save(): void {
    form.state = props.currentState();
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            saveOpen.value = false;
        },
    });
}

function overwrite(view: TableViewItem): void {
    router.patch(
        update.url(view.id),
        { state: props.currentState() },
        { preserveScroll: true },
    );
    listOpen.value = false;
}

function toggleDefault(view: TableViewItem): void {
    router.patch(
        update.url(view.id),
        { is_default: !view.is_default },
        { preserveScroll: true, preserveState: true, only: ['views'] },
    );
}

function askDelete(view: TableViewItem): void {
    deleting.value = view;
    deleteOpen.value = true;
    listOpen.value = false;
}

function confirmDelete(): void {
    if (!deleting.value) {
        return;
    }

    deleteProcessing.value = true;
    router.delete(destroy.url(deleting.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleteOpen.value = false;
        },
    });
}
</script>

<template>
    <Popover v-model:open="listOpen">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                class="max-w-52 font-normal"
                :class="activeView ? 'border-primary text-primary' : ''"
            >
                <Bookmark aria-hidden="true" />
                <span class="truncate">{{
                    activeView ? activeView.name : 'Views'
                }}</span>
            </Button>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-80 p-3">
            <p class="mb-2 text-sm font-semibold">Saved views</p>

            <p
                v-if="views.length === 0"
                class="rounded-sm bg-muted px-3 py-2 text-sm text-muted-foreground"
            >
                No saved views yet. Set up the filters, sort and columns you
                want, then save them under a name.
            </p>

            <ul v-else class="grid gap-0.5">
                <li
                    v-for="view in views"
                    :key="view.id"
                    class="flex items-center gap-1 rounded-sm hover:bg-accent"
                    :class="activeView?.id === view.id ? 'bg-accent' : ''"
                >
                    <button
                        type="button"
                        class="flex min-h-9 min-w-0 flex-1 items-center gap-2 px-2 text-left text-sm"
                        :aria-current="
                            activeView?.id === view.id ? 'true' : undefined
                        "
                        @click="
                            emit('apply', view);
                            listOpen = false;
                        "
                    >
                        <span class="truncate">{{ view.name }}</span>
                        <span
                            v-if="view.is_shared"
                            class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
                        >
                            <Users class="size-3" aria-hidden="true" />
                            {{ view.mine ? 'Shared' : view.owner }}
                        </span>
                    </button>
                    <Button
                        v-if="view.mine"
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="size-8"
                        :aria-label="
                            view.is_default
                                ? `Stop using ${view.name} as your default`
                                : `Open ${view.name} by default`
                        "
                        :aria-pressed="view.is_default"
                        @click="toggleDefault(view)"
                    >
                        <Star
                            class="size-4"
                            :class="
                                view.is_default
                                    ? 'text-warning-600 fill-current'
                                    : ''
                            "
                            aria-hidden="true"
                        />
                    </Button>
                    <Button
                        v-if="view.mine"
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="size-8"
                        :aria-label="`Delete view ${view.name}`"
                        @click="askDelete(view)"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                    </Button>
                </li>
            </ul>

            <div class="mt-3 grid gap-1 border-t pt-3">
                <Button
                    v-if="activeView?.mine"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="justify-start"
                    @click="overwrite(activeView)"
                >
                    Update “{{ activeView.name }}” with what you see now
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="justify-start"
                    @click="openSave"
                >
                    Save what you see now as a new view…
                </Button>
            </div>
        </PopoverContent>
    </Popover>

    <Dialog v-model:open="saveOpen">
        <DialogContent class="sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="save">
                <DialogHeader>
                    <DialogTitle>Save view</DialogTitle>
                    <DialogDescription>
                        Keeps the search, filters, sort, columns, widths, rows
                        per page and density as they are now.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label :for="`${scope}-view-name`">Name</Label>
                    <Input
                        :id="`${scope}-view-name`"
                        v-model="form.name"
                        maxlength="60"
                        required
                        placeholder="e.g. Inactive users"
                        :aria-invalid="!!form.errors.name || undefined"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ form.name.length }} / 60
                    </p>
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-3">
                    <label
                        v-if="canShare"
                        class="flex items-start gap-3 text-sm"
                    >
                        <Checkbox
                            :model-value="form.is_shared"
                            class="mt-0.5"
                            @update:model-value="
                                (value) => (form.is_shared = value === true)
                            "
                        />
                        <span>
                            Share with everyone who can open this table
                            <span class="block text-xs text-muted-foreground"
                                >Others can use it but only you can change
                                it.</span
                            >
                        </span>
                    </label>
                    <InputError :message="form.errors.is_shared" />
                    <label class="flex items-start gap-3 text-sm">
                        <Checkbox
                            :model-value="form.is_default"
                            class="mt-0.5"
                            @update:model-value="
                                (value) => (form.is_default = value === true)
                            "
                        />
                        <span>
                            Open this view by default
                            <span class="block text-xs text-muted-foreground"
                                >Used when you open the table without a
                                filter.</span
                            >
                        </span>
                    </label>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="saveOpen = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="form.processing || form.name.trim() === ''"
                    >
                        <Spinner v-if="form.processing" />
                        Save view
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="deleteOpen"
        :title="`Delete the view “${deleting?.name ?? ''}”?`"
        description="The view is removed for you and for everyone it was shared with. The records in the table are not affected."
        confirm-label="Delete view"
        destructive
        :processing="deleteProcessing"
        @confirm="confirmDelete"
    />
</template>
