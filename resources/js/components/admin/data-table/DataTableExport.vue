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

// ADM-TBL-012: the export is a plain download link; the server streams the filtered, sorted rows
// with the visible columns in order. `parameters` is the table state as a query string.
const props = defineProps<{
    tableKey: string;
    parameters: (format: 'csv' | 'xlsx') => string;
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
                <a :href="href('csv')" download
                    >CSV (opens in any spreadsheet)</a
                >
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <a :href="href('xlsx')" download>Excel (.xlsx)</a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
