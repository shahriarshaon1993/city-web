<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/forms/FormField.vue';
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
import { Spinner } from '@/components/ui/spinner';
import { merge } from '@/routes/admin/news/tags';
import type { TagRow } from '@/types';

// FR-NEWS-003: merge other tags into this one. The tags chosen disappear; their posts get this tag once.
const props = defineProps<{
    tag: TagRow | null;
    all: Array<{ id: number; name: string }>;
}>();

const open = defineModel<boolean>('open', { default: false });
const form = useForm<{ ids: number[] }>({ ids: [] });
const search = ref('');

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({ ids: [] });
        form.reset();
        search.value = '';
    }
});

const candidates = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.all.filter(
        (item) =>
            item.id !== props.tag?.id &&
            (term === '' || item.name.toLowerCase().includes(term)),
    );
});

function toggle(id: number, on: boolean): void {
    form.ids = on
        ? [...form.ids, id]
        : form.ids.filter((value) => value !== id);
}

function submit(): void {
    if (!props.tag) {
        return;
    }

    form.post(merge.url(props.tag.id), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Merge into “{{ tag?.name }}”</DialogTitle>
                    <DialogDescription>
                        Choose the tags that mean the same. They are deleted,
                        and every post that had one gets “{{ tag?.name }}”
                        instead.
                    </DialogDescription>
                </DialogHeader>

                <FormField
                    id="tag-merge-ids"
                    label="Tags to merge"
                    :error="form.errors.ids"
                >
                    <template #default>
                        <div class="grid gap-2">
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Search tags"
                                aria-label="Search tags"
                                class="h-9"
                            />
                            <ul
                                class="max-h-64 overflow-y-auto rounded-md border"
                                role="group"
                                aria-label="Tags to merge"
                            >
                                <li
                                    v-for="item in candidates"
                                    :key="item.id"
                                    class="border-b last:border-b-0"
                                >
                                    <label
                                        class="flex min-h-10 cursor-pointer items-center gap-3 px-3 text-sm hover:bg-muted/50"
                                    >
                                        <Checkbox
                                            :model-value="
                                                form.ids.includes(item.id)
                                            "
                                            @update:model-value="
                                                toggle(item.id, $event === true)
                                            "
                                        />
                                        {{ item.name }}
                                    </label>
                                </li>
                                <li
                                    v-if="candidates.length === 0"
                                    class="px-3 py-4 text-sm text-muted-foreground"
                                >
                                    No other tags match.
                                </li>
                            </ul>
                            <p class="text-xs text-muted-foreground">
                                {{ form.ids.length }} selected
                            </p>
                        </div>
                    </template>
                </FormField>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        :disabled="form.processing || form.ids.length === 0"
                    >
                        <Spinner v-if="form.processing" />
                        Merge tags
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
