<script setup lang="ts">
import { Check, FileText, Film, ImageOff, Loader } from '@lucide/vue';
import { computed } from 'vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { MediaAsset } from '@/types';

// CMP-ADM-MEDIA tile: borderless 4:3 photo on the item's dominant colour with the file name and
// dimensions · size underneath. Missing alt = warning badge with a tooltip (design/admin.md §12.6, DP-03).
// The tile itself opens the item (library) or picks it (picker); the checkbox selects it.
const props = withDefaults(
    defineProps<{
        asset: MediaAsset;
        selected?: boolean;
        /** Show the selection checkbox (library bulk actions). */
        selectable?: boolean;
        /** Picker mode: the whole tile toggles the selection. */
        pickMode?: boolean;
        search?: string;
    }>(),
    { selected: false, selectable: false, pickMode: false, search: '' },
);

const emit = defineEmits<{ open: []; toggle: [value: boolean] }>();

const meta = computed(() =>
    [
        props.asset.width && props.asset.height
            ? `${props.asset.width} × ${props.asset.height}`
            : props.asset.duration_s
              ? `${props.asset.duration_s} s`
              : null,
        props.asset.size_label,
    ]
        .filter(Boolean)
        .join(' · '),
);

function activate(): void {
    if (props.pickMode) {
        emit('toggle', !props.selected);
    } else {
        emit('open');
    }
}
</script>

<template>
    <div class="group relative">
        <button
            type="button"
            class="block w-full rounded-xl text-left outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
            :aria-pressed="pickMode ? selected : undefined"
            :aria-label="`${pickMode ? 'Pick' : 'Open'} ${asset.title}`"
            @click="activate"
        >
            <span
                :class="
                    cn(
                        'relative block aspect-[4/3] overflow-hidden rounded-xl bg-muted ring-1 ring-foreground/10 transition-shadow duration-(--dur-fast)',
                        selected
                            ? 'ring-2 ring-primary'
                            : 'group-hover:shadow-md group-hover:ring-foreground/20',
                    )
                "
                :style="
                    asset.dominant_color
                        ? { backgroundColor: asset.dominant_color }
                        : undefined
                "
            >
                <img
                    v-if="asset.thumb_url"
                    :src="asset.thumb_url"
                    :alt="asset.alt ?? ''"
                    loading="lazy"
                    decoding="async"
                    :width="asset.width ?? undefined"
                    :height="asset.height ?? undefined"
                    :class="
                        cn(
                            'size-full transition-transform duration-(--dur-slow) ease-out motion-reduce:transition-none',
                            asset.kind === 'svg'
                                ? 'object-contain p-4'
                                : 'object-cover motion-safe:group-hover:scale-[1.03]',
                        )
                    "
                    :style="{
                        objectPosition: `${asset.focal.x}% ${asset.focal.y}%`,
                    }"
                />
                <span
                    v-else
                    class="grid size-full place-items-center text-muted-foreground"
                >
                    <span
                        class="grid size-14 place-items-center rounded-full bg-background/80 shadow-xs"
                    >
                        <Film
                            v-if="asset.kind === 'video'"
                            class="size-6"
                            aria-hidden="true"
                        />
                        <FileText v-else class="size-6" aria-hidden="true" />
                    </span>
                </span>

                <!-- kind badge: only for what is not a plain photo -->
                <span
                    v-if="asset.kind !== 'image'"
                    class="absolute right-2 bottom-2 inline-flex items-center rounded-full bg-foreground/75 px-2 py-0.5 text-[11px] font-medium text-background backdrop-blur-sm"
                >
                    {{ asset.kind_label }}
                </span>

                <span
                    v-if="asset.conversions_status === 'pending'"
                    class="absolute bottom-2 left-2 inline-flex items-center gap-1 rounded-full bg-background/90 px-2 py-0.5 text-[11px] shadow-xs"
                >
                    <Loader
                        class="size-3 animate-spin motion-reduce:animate-none"
                        aria-hidden="true"
                    />
                    Preparing sizes
                </span>
                <span
                    v-else-if="asset.conversions_status === 'failed'"
                    class="absolute bottom-2 left-2 rounded-full bg-background/90 px-2 py-0.5 text-[11px] text-destructive shadow-xs"
                >
                    Sizes failed
                </span>
            </span>

            <span class="block px-1 pt-2.5">
                <span
                    class="block truncate text-sm font-medium"
                    :title="asset.title"
                >
                    <HighlightText :text="asset.title" :term="search" />
                </span>
                <span
                    class="mt-0.5 block truncate text-xs text-muted-foreground tabular-nums"
                >
                    {{ meta || '–' }}
                </span>
            </span>
        </button>

        <!-- design/admin.md §12.6: warning mark on tiles without alt text (DP-03) -->
        <Tooltip v-if="asset.missing_alt">
            <TooltipTrigger as-child>
                <span
                    tabindex="0"
                    class="absolute top-2 right-2 grid size-6 place-items-center rounded-full bg-warning text-warning-foreground shadow-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Missing alt text"
                >
                    <ImageOff class="size-3.5" aria-hidden="true" />
                </span>
            </TooltipTrigger>
            <TooltipContent
                >Missing alt text: describe the image, or mark it
                decorative.</TooltipContent
            >
        </Tooltip>

        <div
            v-if="selectable && !pickMode"
            :class="
                cn(
                    'absolute top-2 left-2 rounded-md bg-background/95 p-1 shadow-sm transition-opacity',
                    selected
                        ? 'opacity-100'
                        : 'opacity-100 pointer-fine:opacity-0 pointer-fine:group-focus-within:opacity-100 pointer-fine:group-hover:opacity-100',
                )
            "
        >
            <Checkbox
                :model-value="selected"
                :aria-label="`Select ${asset.title}`"
                @update:model-value="(value) => emit('toggle', value === true)"
            />
        </div>
        <span
            v-if="pickMode && selected"
            class="absolute top-2 left-2 grid size-6 place-items-center rounded-full bg-primary text-primary-foreground"
            aria-hidden="true"
        >
            <Check class="size-4" />
        </span>
    </div>
</template>
