<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { destroy } from '@/routes/admin/news/categories';
import type { PostCategoryRow } from '@/types';

// DEL-03: a category with posts cannot just be deleted. The dialog names the posts and asks where they
// go ("Move posts to…"); the category then moves to the Recycle bin. Restoring it does not move them back.
const props = defineProps<{
    category: PostCategoryRow | null;
    others: Array<{ id: number; name: string }>;
}>();

const open = defineModel<boolean>('open', { default: false });

const target = ref<number | null>(null);
const busy = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        target.value = props.others[0]?.id ?? null;
        busy.value = false;
    }
});

const options = computed(() =>
    props.others.map((other) => ({ value: other.id, label: other.name })),
);

const count = computed(() => props.category?.posts_count ?? 0);

function submit(): void {
    if (!props.category || target.value === null) {
        return;
    }

    router.delete(destroy.url(props.category.id), {
        data: { move_to: target.value },
        preserveScroll: true,
        onStart: () => {
            busy.value = true;
        },
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            busy.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Move the posts first</DialogTitle>
                    <DialogDescription>
                        “{{ category?.name }}” has {{ count }}
                        {{ count === 1 ? 'post' : 'posts' }}. Every post needs a
                        category, so choose where they go. The category then
                        moves to the Recycle bin.
                    </DialogDescription>
                </DialogHeader>

                <ul v-if="category?.posts.length" class="grid gap-1 text-sm">
                    <li v-for="item in category.posts" :key="item.href">
                        <Link
                            :href="item.href"
                            class="inline-flex min-h-9 items-center underline underline-offset-4 hover:text-primary"
                            >{{ item.label }}</Link
                        >
                    </li>
                    <li
                        v-if="count > category.posts.length"
                        class="text-muted-foreground"
                    >
                        and {{ count - category.posts.length }} more
                    </li>
                </ul>

                <p
                    v-if="options.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    There is no other category to move them to. Create one
                    first.
                </p>
                <ChoiceField
                    v-else
                    id="category-move-to"
                    v-model="target"
                    label="Move posts to"
                    required
                    :options="options"
                />

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="busy || target === null"
                    >
                        <Spinner v-if="busy" />
                        Move posts and delete
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
