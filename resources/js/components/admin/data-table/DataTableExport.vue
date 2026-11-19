<script setup lang="ts">
import { Download } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { exportMethod } from '@/routes/admin/tables';

// ADM-TBL-012: the export is a plain link; the server streams the filtered, sorted rows with the
// visible columns in order, or above 5,000 rows queues the file and comes back with a toast.
// `parameters` is the table state as a query string.
const QUEUE_THRESHOLD = 5000;

const props = defineProps<{
    tableKey: string;
    parameters: (format: 'csv' | 'xlsx') => string;
    /** Rows in the filtered table. */
    total: number;
}>();

function href(format: 'csv' | 'xlsx'): string {
    return `${exportMethod.url({ table: props.tableKey })}?${props.parameters(format)}`;
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="font-normal">
                <Download aria-hidden="true" />
                Export
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-60">
            <DropdownMenuLabel
                class="text-xs font-normal text-muted-foreground"
            >
                The rows you filtered, with the columns you can see.
            </DropdownMenuLabel>
            <DropdownMenuItem as-child>
                <a :href="href('csv')">CSV (opens in any spreadsheet)</a>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <a :href="href('xlsx')">Excel (.xlsx)</a>
            </DropdownMenuItem>
            <DropdownMenuLabel
                v-if="total > QUEUE_THRESHOLD"
                class="text-xs font-normal text-muted-foreground"
            >
                More than 5,000 rows are prepared in the background. You get a
                notification with a link that works for 24 hours.
            </DropdownMenuLabel>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
