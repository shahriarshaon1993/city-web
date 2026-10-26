<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { computed } from 'vue';
import { fieldControlClass } from '@/components/ui/input';
import { useListing } from '@/composables/useProjectListing';
import { cn } from '@/lib/utils';

// FR-SRCH-001 keyword (`q`, ≤ 80 chars, FR-SRCH-011): a real GET form, so
// Enter works without JS (the other filters ride along as hidden inputs and
// the server normalises the URL). With JS typing reloads after 350 ms with
// `replace` (FR-SRCH-003), Enter applies at once.
const props = defineProps<{
    idPrefix: string;
    class?: string;
}>();

const listing = useListing();

const target = computed(() => {
    const [path, query = ''] = listing.urlFor({ q: '' }).split('?');

    return { path, hidden: [...new URLSearchParams(query).entries()] };
});

const id = computed(() => `${props.idPrefix}-q`);
</script>

<template>
    <form
        role="search"
        :action="target.path"
        method="get"
        :class="cn('relative min-w-0', props.class)"
        @submit.prevent="listing.flushTyping()"
    >
        <label :for="id" class="sr-only">Search by name or area</label>
        <Search
            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <input
            :id="id"
            :data-filter-key="id"
            type="search"
            name="q"
            maxlength="80"
            autocomplete="off"
            enterkeyhint="search"
            placeholder="Search by name or area"
            :value="listing.state.q"
            :class="
                cn(
                    fieldControlClass,
                    'h-11 pr-10 pl-10 [&::-webkit-search-cancel-button]:hidden',
                )
            "
            @input="listing.type(($event.target as HTMLInputElement).value)"
        />
        <input
            v-for="[name, value] in target.hidden"
            :key="`${name}=${value}`"
            type="hidden"
            :name="name"
            :value="value"
        />
        <button
            v-if="listing.state.q"
            type="button"
            aria-label="Clear search"
            class="absolute top-1/2 right-0.5 flex size-10 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground hover:text-foreground"
            @click="listing.update({ q: '' })"
        >
            <X class="size-4" aria-hidden="true" />
        </button>
    </form>
</template>
