<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, onMounted, ref, useId } from 'vue';
import { Switch } from '@/components/ui/switch';
import { setReduceMotion, storedReduceMotion } from '@/motion/preference';
import type { SiteShared } from '@/types/site';

// CMP-FOOT-01 / SEO-A11Y-005: the footer "Reduce motion" switch. Stored in
// localStorage (`gc.reduce-motion`) and applied at once (SiteLayout stops or
// starts the runtime). When the device already asks for reduced motion the
// switch shows on, disabled, and says why. Hidden when Settings → Motion
// turned animations off for everyone. SSR renders it off; the stored choice
// is read on mount.
const page = usePage<SiteShared>();
const available = computed(() => page.props.ui.motion.animations);

const bySystem = useMediaQuery('(prefers-reduced-motion: reduce)');
const stored = ref(false);
const checked = computed(() => bySystem.value || stored.value);

const id = useId();
const hintId = useId();

onMounted(() => {
    stored.value = storedReduceMotion();
});

function update(value: boolean): void {
    stored.value = value;
    setReduceMotion(value);
}
</script>

<template>
    <div v-if="available" class="flex min-h-11 items-center gap-3">
        <Switch
            :id="id"
            :model-value="checked"
            :disabled="bySystem"
            :aria-describedby="bySystem ? hintId : undefined"
            @update:model-value="update"
        />
        <label
            :for="id"
            class="text-muted-foreground"
            :class="bySystem ? 'cursor-not-allowed' : 'cursor-pointer'"
        >
            Reduce motion
        </label>
        <span v-if="bySystem" :id="hintId" class="text-muted-foreground/80">
            On in your device settings
        </span>
    </div>
</template>
