<script setup lang="ts">
import { computed } from 'vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import type { FormatSample } from '@/components/styleguide/types';
import { Badge } from '@/components/ui/badge';
import {
    formatBdt,
    formatBdtCompact,
    formatBdtRange,
    formatDate,
    formatKatha,
    formatMonth,
    formatPhone,
    formatSft,
    formatSftRange,
} from '@/lib/format';

// ARC-017: PHP (server) and TS (here) must agree, and match the spec strings.
const props = defineProps<{ samples: FormatSample[] }>();

const formatters: Record<FormatSample['fn'], (...args: never[]) => string> = {
    formatBdt,
    formatBdtCompact,
    formatBdtRange,
    formatSft,
    formatSftRange,
    formatKatha,
    formatDate,
    formatMonth,
    formatPhone,
};

const rows = computed(() =>
    props.samples.map((sample) => {
        const run = formatters[sample.fn] as (...args: unknown[]) => string;
        const ts = run(...sample.args);
        const plain = sample.php.replaceAll('\u00a0', ' ');

        return {
            ...sample,
            call: `${sample.fn}(${sample.args.map((arg) => JSON.stringify(arg)).join(', ')})`,
            ts,
            matches:
                ts === sample.php &&
                (sample.expected === null || plain === sample.expected),
        };
    }),
);

const columns = ['Call', 'Spec', 'PHP', 'TS', 'Match'];
</script>

<template>
    <StyleguideSection id="sg-format" title="Formatters">
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-left text-body">
                <thead class="bg-muted">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column"
                            scope="col"
                            class="px-3 py-2"
                        >
                            {{ column }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.call" class="border-t">
                        <td class="px-3 py-2">
                            <code>{{ row.call }}</code>
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">
                            {{ row.expected ?? '—' }}
                        </td>
                        <td class="px-3 py-2">{{ row.php }}</td>
                        <td class="px-3 py-2">{{ row.ts }}</td>
                        <td class="px-3 py-2">
                            <Badge
                                :variant="row.matches ? 'lifecycle' : 'booking'"
                                :value="row.matches ? 'ready' : 'sold_out'"
                            >
                                {{ row.matches ? 'Yes' : 'No' }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </StyleguideSection>
</template>
