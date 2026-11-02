<script setup lang="ts">
import { Mail } from '@lucide/vue';
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { cn } from '@/lib/utils';
import type { TeamGridBlock } from '@/types/blocks';

// CMP-BLK-TEAM / CMP-PERSON-01 in the premium language: 4:5 portraits with no
// card around them, light name, overline role, bio and LinkedIn / email as
// quiet icon links. Leadership shows three across (bios are longer), the
// other groups four. Portraits are grey until hovered or focused with a fine
// pointer and motion allowed (MOT-PORTRAIT, `.portrait-tone` in app.css);
// they are in colour on touch and under reduced motion. Without a block
// heading the names become H2 (CMS-PB-004).
const props = defineProps<{
    block: TeamGridBlock;
    first: boolean;
}>();

const nameTag = computed(() => (props.block.data.heading ? 'h3' : 'h2'));
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <ul
            v-reveal.stagger
            :class="
                cn(
                    'grid gap-x-6 gap-y-12 xl:gap-x-8',
                    block.data.group === 'leadership'
                        ? 'sm:grid-cols-2 lg:grid-cols-3'
                        : 'grid-cols-2 lg:grid-cols-4',
                )
            "
        >
            <li v-for="member in block.data.members" :key="member.id">
                <article class="team-card flex flex-col gap-5">
                    <ResponsiveImage
                        :image="member.photo"
                        preset="team-portrait"
                        img-class="portrait-tone"
                        class="rounded-xl"
                    />
                    <div class="flex flex-col gap-2">
                        <component
                            :is="nameTag"
                            class="font-display text-h4 text-balance"
                        >
                            {{ member.name }}
                        </component>
                        <p class="text-overline text-primary uppercase">
                            {{ member.role }}
                        </p>
                        <p
                            v-if="member.bio"
                            class="mt-1 max-w-prose text-sm text-pretty text-muted-foreground"
                        >
                            {{ member.bio }}
                        </p>
                        <div
                            v-if="member.linkedin_url || member.email"
                            class="-ml-3 flex items-center"
                        >
                            <a
                                v-if="member.linkedin_url"
                                :href="member.linkedin_url"
                                target="_blank"
                                rel="noopener"
                                class="flex size-11 items-center justify-center text-muted-foreground transition-colors duration-(--dur-fast) hover:text-primary"
                            >
                                <SocialIcon network="linkedin" class="size-5" />
                                <span class="sr-only">
                                    {{ member.name }} on LinkedIn (opens in a
                                    new tab)
                                </span>
                            </a>
                            <a
                                v-if="member.email"
                                :href="`mailto:${member.email}`"
                                class="flex size-11 items-center justify-center text-muted-foreground transition-colors duration-(--dur-fast) hover:text-primary"
                            >
                                <Mail
                                    class="size-5"
                                    stroke-width="1.75"
                                    aria-hidden="true"
                                />
                                <span class="sr-only">
                                    Email {{ member.name }}
                                </span>
                            </a>
                        </div>
                    </div>
                </article>
            </li>
        </ul>
    </BlockSection>
</template>
