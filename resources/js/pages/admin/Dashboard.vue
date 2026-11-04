<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import { adminIcon } from '@/lib/admin-icons';

const page = usePage();
const user = computed(() => page.props.auth.user);
const firstName = computed(() => user.value.name.split(/\s+/u)[0]);
// Everything except the dashboard itself: these are the sections this role may open.
const groups = computed(() =>
    page.props.navigation
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => item.key !== 'dashboard'),
        }))
        .filter((group) => group.items.length > 0),
);
</script>

<template>
    <Head title="Dashboard" />

    <AdminPageHeader
        title="Dashboard"
        :description="`Welcome, ${firstName}. You are signed in as ${user.roles.join(', ')}.`"
    />

    <section
        aria-labelledby="sections-heading"
        class="rounded-lg border bg-card p-5 md:p-6"
    >
        <h2 id="sections-heading" class="text-lg font-semibold">
            What you can open
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">
            The numbers, follow-ups and shortcuts of this page arrive in phase
            18.1. Until then, these are the sections your role can reach.
        </p>

        <div class="mt-6 grid gap-x-10 gap-y-6 md:grid-cols-2 xl:grid-cols-3">
            <div v-for="group in groups" :key="group.label">
                <h3
                    class="mb-2 text-overline tracking-[0.16em] text-muted-foreground uppercase"
                >
                    {{ group.label }}
                </h3>
                <ul class="divide-y border-y">
                    <li v-for="item in group.items" :key="item.key">
                        <Link
                            :href="item.href"
                            class="flex min-h-11 items-center gap-3 py-2 text-sm hover:text-primary"
                        >
                            <component
                                :is="adminIcon(item.icon)"
                                class="size-5 text-muted-foreground"
                                aria-hidden="true"
                            />
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
