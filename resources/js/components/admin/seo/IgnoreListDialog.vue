<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { ignoreList } from '@/routes/admin/not-found';
import type { IgnoreList } from '@/types';

// SEO-TEC-009: addresses matching one of these regular expressions never reach the monitor.
const props = defineProps<{ list: IgnoreList }>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ patterns: '' });
const isDefault = computed(() => form.patterns === props.list.defaults);

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.defaults({ patterns: props.list.patterns });
        form.reset();
    }
});

function save(useDefaults = false): void {
    form.transform((data) => ({ ...data, reset: useDefaults }));
    form.put(ignoreList.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-xl">
            <form class="grid gap-5" @submit.prevent="save()">
                <DialogHeader>
                    <DialogTitle>Ignore list</DialogTitle>
                    <DialogDescription>
                        Missing addresses that match a pattern are not logged —
                        useful for bots probing for WordPress and for missing
                        icons or fonts. One regular expression per line, matched
                        against the path without regard to case.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="ignore-patterns">Patterns</Label>
                    <Textarea
                        id="ignore-patterns"
                        v-model="form.patterns"
                        rows="7"
                        maxlength="5000"
                        spellcheck="false"
                        autocapitalize="off"
                        class="font-mono text-sm"
                        aria-describedby="ignore-patterns-help"
                        :aria-invalid="!!form.errors.patterns || undefined"
                    />
                    <FieldHelp id="ignore-patterns">
                        Example: <span class="font-mono">^/wp-</span> ignores
                        everything starting with /wp-. Changes apply to new
                        404s; rows already in the monitor stay until you ignore
                        or delete them.
                    </FieldHelp>
                    <InputError :message="form.errors.patterns" />
                </div>

                <DialogFooter class="sm:justify-between">
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="
                            form.processing || (isDefault && !list.is_custom)
                        "
                        @click="save(true)"
                    >
                        Use the defaults
                    </Button>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <Button
                            type="button"
                            variant="outline"
                            @click="open = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            Save list
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
