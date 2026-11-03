<script setup lang="ts">
import { Link2 } from '@lucide/vue';
import ClientOnly from '@/components/site/ClientOnly.vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { notify } from '@/lib/toast';
import type { ShareData } from '@/types/news';

// CMP-SHARE-01: Copy link (toast), Facebook, LinkedIn and WhatsApp share
// links; no SDKs, so nothing third-party loads before a click. The share
// links are plain anchors and work without JS; Copy link needs the
// clipboard, so it appears after mount (SEO-REN-08).
const props = defineProps<{
    share: ShareData;
}>();

async function copyLink(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.share.url);
        notify.success(props.share.copied_message);
    } catch {
        notify.error(props.share.failed_message);
    }
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <p class="text-overline text-muted-foreground uppercase">
            {{ share.heading }}
        </p>
        <ul class="flex flex-wrap items-center gap-3">
            <li>
                <ClientOnly>
                    <Button type="button" variant="outline" @click="copyLink">
                        <Link2 aria-hidden="true" />
                        {{ share.copy_label }}
                    </Button>
                </ClientOnly>
            </li>
            <li v-for="link in share.links" :key="link.network">
                <Button as-child variant="outline">
                    <a
                        :href="link.href"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <SocialIcon :network="link.network" class="size-4" />
                        {{ link.label }}
                        <span class="sr-only">(opens in a new tab)</span>
                    </a>
                </Button>
            </li>
        </ul>
    </div>
</template>
