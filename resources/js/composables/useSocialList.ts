import { router, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, Trash2 } from '@lucide/vue';
import { computed, reactive } from 'vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import { useConfirm } from '@/composables/useConfirm';
import type {
    DataTableBulkAction,
    FilterOption,
    TableSelection,
} from '@/types';

type Route = { url: (...args: never[]) => string };

type Options<Row extends { id: number; is_published?: boolean }> = {
    /** Wayfinder routes of the list; `published` is left out for offices. */
    routes: {
        index: string;
        published?: { url: (id: number) => string };
        destroy: { url: (id: number) => string };
        bulk: Route;
    };
    /** ['testimonial', 'testimonials']. */
    nouns: [string, string];
    /** What the delete confirm says happens to the website. */
    deleteEffect: string;
    label: (row: Row) => string;
    canManage: () => boolean;
    filterOptions: () => Record<string, FilterOption[]>;
};

// The parts every ADM-SCR-09 list shares (CRUD-01/02, DEL-02, ADM-TBL-009): the inline Published
// switch with an optimistic update, Delete with a confirm, the bulk bar and the Published / Hidden
// status tabs. Each list page keeps only its columns and cells.
export function useSocialList<
    Row extends { id: number; is_published?: boolean },
>(options: Options<Row>) {
    const confirm = useConfirm();
    const page = usePage();
    const pending = reactive<Record<number, boolean>>({});
    const [one, many] = options.nouns;
    const noun = (count: number): string => (count === 1 ? one : many);

    function isPublished(row: Row): boolean {
        return pending[row.id] ?? row.is_published ?? true;
    }

    function togglePublished(row: Row, value: boolean): void {
        if (!options.routes.published) {
            return;
        }

        pending[row.id] = value;
        router.patch(
            options.routes.published.url(row.id),
            { published: value },
            {
                preserveScroll: true,
                onFinish: () => {
                    delete pending[row.id];
                },
            },
        );
    }

    async function deleteOne(row: Row): Promise<void> {
        const answer = await confirm({
            title: `Delete this ${one}?`,
            subject: options.label(row),
            description: `${options.deleteEffect} It moves to the Recycle bin and can be restored for 90 days.`,
            confirmLabel: `Delete ${one}`,
            destructive: true,
        });

        if (answer) {
            router.delete(options.routes.destroy.url(row.id), {
                preserveScroll: true,
            });
        }
    }

    function postBulk(
        action: 'publish' | 'unpublish' | 'delete',
        selection: TableSelection,
    ): void {
        router.post(
            (options.routes.bulk.url as () => string)(),
            {
                action,
                ...(selection.all
                    ? { all: true, ...selection.query }
                    : { ids: selection.ids }),
            },
            { preserveScroll: true },
        );
    }

    async function deleteMany(selection: TableSelection): Promise<void> {
        const answer = await confirm({
            title: `Delete ${selection.count} ${noun(selection.count)}?`,
            description: `${options.deleteEffect} They move to the Recycle bin and can be restored for 90 days.`,
            confirmLabel: `Delete ${noun(selection.count)}`,
            destructive: true,
            typeToConfirm:
                selection.count > 20 ? `DELETE ${selection.count}` : undefined,
        });

        if (answer) {
            postBulk('delete', selection);
        }
    }

    const bulkActions = computed<DataTableBulkAction[]>(() => {
        if (!options.canManage()) {
            return [];
        }

        return [
            ...(options.routes.published
                ? [
                      {
                          id: 'publish',
                          label: 'Publish',
                          icon: Eye,
                          run: (selection: TableSelection) =>
                              postBulk('publish', selection),
                      },
                      {
                          id: 'unpublish',
                          label: 'Unpublish',
                          icon: EyeOff,
                          run: (selection: TableSelection) =>
                              postBulk('unpublish', selection),
                      },
                  ]
                : []),
            {
                id: 'delete',
                label: 'Delete',
                icon: Trash2,
                destructive: true,
                run: (selection: TableSelection) => void deleteMany(selection),
            },
        ];
    });

    // CRUD-02: status tabs with counts (links that set `filter[status]`).
    const currentStatus = computed(
        () =>
            new URLSearchParams(page.url.split('?')[1] ?? '').get(
                'filter[status]',
            ) ?? '',
    );

    const statusTabs = computed<ListTab[]>(() => {
        const statuses = options.filterOptions().status ?? [];

        if (statuses.length === 0) {
            return [];
        }

        const total = statuses.reduce(
            (sum, item) => sum + (item.count ?? 0),
            0,
        );
        const tab = (key: string, label: string, count: number): ListTab => ({
            key,
            label,
            count,
            href:
                key === ''
                    ? options.routes.index
                    : `${options.routes.index}?filter[status]=${key}`,
            active: currentStatus.value === key,
        });

        return [
            tab('', 'All', total),
            ...statuses.map((item) =>
                tab(item.value, item.label, item.count ?? 0),
            ),
        ];
    });

    return {
        isPublished,
        togglePublished,
        deleteOne,
        bulkActions,
        statusTabs,
    };
}
