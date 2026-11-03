<script setup lang="ts">
import { reactive, useId, watch } from 'vue';
import FormDialog from '@/components/forms/FormDialog.vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useConsent } from '@/composables/useConsent';
import { notify } from '@/lib/toast';

// CMP-COOKIE-01 preferences: one switch per category (essential is always
// on), opened from the banner or from the footer's "Cookie settings". Starts
// from the stored choice, otherwise everything optional is off (no
// pre-ticked boxes, NFR-PRIV-01).
const { cookies, consent, preferencesOpen, opener, save } = useConsent();

const uid = useId();
const choice = reactive({ analytics: false, marketing: false });

watch(preferencesOpen, (open) => {
    if (open) {
        choice.analytics = consent.value?.analytics ?? false;
        choice.marketing = consent.value?.marketing ?? false;
    }
});

function saveChoice(next: { analytics: boolean; marketing: boolean }): void {
    save(next);
    notify.success('Your cookie preferences are saved.');
}
</script>

<template>
    <FormDialog
        v-model:open="preferencesOpen"
        :title="cookies.title"
        description="Choose which cookies we may use. You can change this at any time from the footer."
        :return-focus="opener"
    >
        <ul class="flex flex-col divide-y divide-border border-y border-border">
            <li
                v-for="category in cookies.categories"
                :key="category.key"
                class="flex items-start justify-between gap-6 py-5"
            >
                <div class="flex flex-col gap-1">
                    <label
                        :for="`${uid}-${category.key}`"
                        class="text-body font-medium"
                    >
                        {{ category.label }}
                    </label>
                    <p
                        :id="`${uid}-${category.key}-text`"
                        class="text-sm text-pretty text-muted-foreground"
                    >
                        {{ category.description }}
                    </p>
                </div>
                <Switch
                    v-if="category.key === 'necessary'"
                    :id="`${uid}-${category.key}`"
                    :model-value="true"
                    disabled
                    :aria-describedby="`${uid}-${category.key}-text`"
                    class="mt-0.5"
                />
                <Switch
                    v-else
                    :id="`${uid}-${category.key}`"
                    v-model="choice[category.key as 'analytics' | 'marketing']"
                    :aria-describedby="`${uid}-${category.key}-text`"
                    class="mt-0.5"
                />
            </li>
        </ul>
        <div class="flex flex-wrap gap-3">
            <Button type="button" @click="saveChoice(choice)">
                Save preferences
            </Button>
            <Button
                type="button"
                variant="outline"
                @click="saveChoice({ analytics: true, marketing: true })"
            >
                Accept all
            </Button>
        </div>
    </FormDialog>
</template>
