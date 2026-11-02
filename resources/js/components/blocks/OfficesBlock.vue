<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import MapFacade from '@/components/site/MapFacade.vue';
import { formatPhone } from '@/lib/format';
import { officeHoursLines } from '@/lib/offices';
import type { OfficesBlock } from '@/types/blocks';

// CMP-BLK-OFFICES (FR-SOC-005): one hairline row per office — name, address
// and a `<dl>` of phone, email and hours in 5 columns beside the CMP-MAP-02
// facade in 7. No cards. The map loads only after "Show interactive map"
// (SEO-PERF-013), "Open in Google Maps" works without JS. Office names are
// H3 under the block's H2 (H2 without a block heading).
const props = defineProps<{
    block: OfficesBlock;
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
        <ul class="flex flex-col divide-y divide-border">
            <li
                v-for="office in block.data.offices"
                :key="office.id"
                class="grid gap-8 py-10 first:pt-0 last:pb-0 lg:grid-cols-12 lg:gap-x-8 xl:gap-x-10"
            >
                <div class="flex flex-col gap-6 lg:col-span-5">
                    <div class="flex flex-col gap-2">
                        <p
                            v-if="office.is_head_office"
                            class="text-overline text-primary uppercase"
                        >
                            {{ office.city }} · Head office
                        </p>
                        <p
                            v-else
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ office.city }}
                        </p>
                        <component
                            :is="nameTag"
                            class="font-display text-h3 text-balance"
                        >
                            {{ office.name }}
                        </component>
                        <address class="text-body text-pretty not-italic">
                            {{ office.address
                            }}<template v-if="office.postal_code"
                                >, {{ office.city }}
                                {{ office.postal_code }}</template
                            >
                        </address>
                    </div>
                    <dl class="flex flex-col gap-4 text-body">
                        <div
                            v-if="office.phones.length > 0"
                            class="flex flex-col gap-1"
                        >
                            <dt
                                class="text-overline text-muted-foreground uppercase"
                            >
                                Phone
                            </dt>
                            <dd v-for="phone in office.phones" :key="phone">
                                <a
                                    :href="`tel:${phone}`"
                                    class="tabular inline-flex min-h-11 items-center underline-offset-4 transition-colors duration-(--dur-fast) hover:text-primary hover:underline"
                                >
                                    {{ formatPhone(phone) }}
                                </a>
                            </dd>
                        </div>
                        <div v-if="office.email" class="flex flex-col gap-1">
                            <dt
                                class="text-overline text-muted-foreground uppercase"
                            >
                                Email
                            </dt>
                            <dd>
                                <a
                                    :href="`mailto:${office.email}`"
                                    class="inline-flex min-h-11 items-center underline-offset-4 transition-colors duration-(--dur-fast) hover:text-primary hover:underline"
                                >
                                    {{ office.email }}
                                </a>
                            </dd>
                        </div>
                        <div
                            v-if="office.hours.length > 0"
                            class="flex flex-col gap-1"
                        >
                            <dt
                                class="text-overline text-muted-foreground uppercase"
                            >
                                Opening hours
                            </dt>
                            <dd
                                v-for="line in officeHoursLines(office)"
                                :key="line"
                                class="tabular"
                            >
                                {{ line }}
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="lg:col-span-7">
                    <MapFacade
                        :lat="office.latitude"
                        :lng="office.longitude"
                        :title="`Map of ${office.name}`"
                        :place="office.name"
                        :address="`${office.address}, ${office.city}`"
                    />
                </div>
            </li>
        </ul>
    </BlockSection>
</template>
