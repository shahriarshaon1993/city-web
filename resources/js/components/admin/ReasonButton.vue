<script setup lang="ts">
import { computed, useAttrs } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

// HELP-08: a button that is not possible right now stays visible and says why. With a
// `reason` it is aria-disabled (still focusable, so keyboard users hear the reason) and
// shows the sentence in a tooltip; without one it behaves like a normal Button.
// Actions the user may never do are hidden instead (`v-if="can(...)"`).
type ButtonProps = InstanceType<typeof Button>['$props'];

defineOptions({ inheritAttrs: false });

const attrs = useAttrs();
const fullWidth = computed(() => String(attrs.class ?? '').includes('w-full'));

const props = defineProps<{
    reason?: string | null;
    variant?: ButtonProps['variant'];
    size?: ButtonProps['size'];
    type?: 'button' | 'submit';
}>();

const emit = defineEmits<{ click: [event: MouseEvent] }>();

const blocked = computed(() => !!props.reason);

function onClick(event: MouseEvent): void {
    if (blocked.value) {
        event.preventDefault();

        return;
    }

    emit('click', event);
}
</script>

<template>
    <Tooltip v-if="blocked">
        <TooltipTrigger as-child>
            <span :class="fullWidth ? 'flex w-full' : 'inline-flex'">
                <Button
                    v-bind="attrs"
                    :variant="variant"
                    :size="size"
                    :type="type ?? 'button'"
                    aria-disabled="true"
                    class="cursor-not-allowed opacity-50"
                    @click="onClick"
                >
                    <slot />
                </Button>
            </span>
        </TooltipTrigger>
        <TooltipContent class="max-w-64">{{ reason }}</TooltipContent>
    </Tooltip>
    <Button
        v-else
        v-bind="attrs"
        :variant="variant"
        :size="size"
        :type="type ?? 'button'"
        @click="onClick"
    >
        <slot />
    </Button>
</template>
