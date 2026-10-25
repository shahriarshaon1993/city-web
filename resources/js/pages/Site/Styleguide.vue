<script setup lang="ts">
import {
    ArrowRight,
    CalendarCheck,
    CircleAlert,
    Heart,
    Phone,
    Search,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/site/EmptyState.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import SkeletonShape from '@/components/site/SkeletonShape.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import type { BadgeValue, BadgeVariant } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
import { notify } from '@/lib/toast';
import type { MediaImage } from '@/types/site';

// Local-only UI kit reference (phases 0.2–0.3). Sample copy here is developer
// tooling, not site content, so it is not props-driven.

const formatters = {
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

type FormatSample = {
    fn: keyof typeof formatters;
    args: (number | string | boolean | null)[];
    php: string;
    expected: string | null;
};

const props = defineProps<{
    images: {
        hero: MediaImage | null;
        hero_mobile: MediaImage | null;
        project: MediaImage | null;
        news: MediaImage | null;
        location: MediaImage | null;
        portrait: MediaImage | null;
        interior: MediaImage | null;
        thumbs: (MediaImage | null)[];
    };
    ogImage: string | null;
    formatSamples: FormatSample[];
}>();

// PHP (server) and TS (here) must agree, and match the spec strings.
const formatRows = computed(() =>
    props.formatSamples.map((sample) => {
        const run = formatters[sample.fn] as (...args: unknown[]) => string;
        const ts = run(...sample.args);
        const plain = sample.php.replaceAll(' ', ' ');

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

const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

const scales: { name: string; hex: string[] }[] = [
    {
        name: 'teal',
        hex: [
            '#EFF9FB',
            '#DDF1F5',
            '#BDE3EA',
            '#91CCD7',
            '#60AFBD',
            '#3392A1',
            '#027685',
            '#055A66',
            '#01464F',
            '#01343C',
            '#001F24',
        ],
    },
    {
        name: 'coral',
        hex: [
            '#FFF4F2',
            '#FFE7E1',
            '#FFCEC2',
            '#FEA794',
            '#EF7B63',
            '#EC6B51',
            '#B8432C',
            '#963826',
            '#782C1D',
            '#5B2014',
            '#3A0F07',
        ],
    },
    {
        name: 'stone',
        hex: [
            '#FEF9F7',
            '#F7F2F0',
            '#EAE5E3',
            '#D9D3D0',
            '#A6A09E',
            '#797371',
            '#5C5755',
            '#46413F',
            '#2F2A28',
            '#1E1918',
            '#100C0B',
        ],
    },
    {
        name: 'success',
        hex: [
            '#EFFAF1',
            '#DDF4E1',
            '#BEE7C5',
            '#93D39F',
            '#62B777',
            '#359A54',
            '#0A7E3A',
            '#03612A',
            '#004B1E',
            '#023816',
            '#00220A',
        ],
    },
    {
        name: 'warning',
        hex: [
            '#FFF5E9',
            '#FDEAD0',
            '#F7D5A6',
            '#EBB66A',
            '#D59219',
            '#AF7603',
            '#8D5E05',
            '#6C4701',
            '#543600',
            '#402801',
            '#271700',
        ],
    },
    {
        name: 'error',
        hex: [
            '#FFF4F2',
            '#FEE7E4',
            '#FECDC7',
            '#FEA69C',
            '#FE6C61',
            '#E2403A',
            '#C2181D',
            '#99020E',
            '#780108',
            '#5D0005',
            '#3A0002',
        ],
    },
    {
        name: 'info',
        hex: [
            '#F1F8FE',
            '#DFEFFE',
            '#BCE0FF',
            '#8AC8FC',
            '#56A9EB',
            '#2389D1',
            '#036EAE',
            '#025486',
            '#024068',
            '#013050',
            '#001C32',
        ],
    },
];

const semanticTokens = [
    ['background', 'foreground'],
    ['card', 'card-foreground'],
    ['primary', 'primary-foreground'],
    ['primary-hover', 'primary-foreground'],
    ['secondary', 'secondary-foreground'],
    ['muted', 'muted-foreground'],
    ['accent', 'accent-foreground'],
    ['brand-accent', 'brand-accent-foreground'],
    ['brand-accent-hover', 'brand-accent-foreground'],
    ['destructive', 'destructive-foreground'],
    ['success', 'success-foreground'],
    ['warning', 'warning-foreground'],
    ['info', 'info-foreground'],
    ['surface-subtle', 'foreground'],
    ['surface-brand', 'foreground'],
] as const;

const lineTokens = ['border', 'input', 'ring', 'brand-accent-decor'] as const;

const gradients = ['brand', 'dusk', 'sunrise', 'mesh'] as const;

const typeScale = [
    {
        token: 'text-display',
        cls: 'font-display text-display',
        sample: 'Homes built to last',
    },
    {
        token: 'text-h1',
        cls: 'font-display text-h1',
        sample: 'Gemcon City Phase 2',
    },
    {
        token: 'text-h2',
        cls: 'font-display text-h2',
        sample: 'Ongoing projects',
    },
    {
        token: 'text-h3',
        cls: 'font-display text-h3',
        sample: 'Unit types and floor plans',
    },
    {
        token: 'text-h4',
        cls: 'font-display text-h4',
        sample: 'Gemcon Lake View',
    },
    {
        token: 'text-lead',
        cls: 'text-lead',
        sample: 'Apartments and plots in Dhaka with RAJUK approval.',
    },
    {
        token: 'text-body-lg',
        cls: 'text-body-lg',
        sample: 'Article body from 768 px up, 18 px with 1.65 line height.',
    },
    {
        token: 'text-body',
        cls: 'text-body',
        sample: 'Body copy, 16 px Inter, 1.65 line height for calm reading.',
    },
    {
        token: 'text-sm',
        cls: 'text-sm',
        sample: 'UI labels, captions and table cells at 14 px.',
    },
    {
        token: 'text-xs',
        cls: 'text-xs',
        sample: 'Meta and helper text at 13 px — the smallest size.',
    },
    {
        token: 'text-overline',
        cls: 'text-overline uppercase text-muted-foreground',
        sample: 'Featured projects',
    },
    {
        token: 'text-stat',
        cls: 'font-display text-stat tabular',
        sample: '1,250+',
    },
    {
        token: 'text-price',
        cls: 'font-display text-price tabular',
        sample: '৳ 1.45 crore',
    },
    {
        token: 'text-fact',
        cls: 'font-display text-fact tabular',
        sample: '1,650–2,100 sft',
    },
];

const radii = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'] as const;
const shadows = ['xs', 'sm', 'md', 'lg', 'xl'] as const;

const buttonVariantList: NonNullable<ButtonVariants['variant']>[] = [
    'primary',
    'accent',
    'secondary',
    'outline',
    'ghost',
    'link',
    'glow',
    'destructive',
];

const badges: { variant: BadgeVariant; value?: BadgeValue; label: string }[] = [
    { variant: 'lifecycle', value: 'upcoming', label: 'Upcoming' },
    { variant: 'lifecycle', value: 'ongoing', label: 'Ongoing' },
    { variant: 'lifecycle', value: 'ready', label: 'Ready' },
    { variant: 'lifecycle', value: 'handed_over', label: 'Handed over' },
    { variant: 'booking', value: 'booking_open', label: 'Booking open' },
    { variant: 'booking', value: 'limited_units', label: 'Limited units' },
    { variant: 'booking', value: 'sold_out', label: 'Sold out' },
    { variant: 'featured', label: 'Featured' },
    { variant: 'neutral', label: '3 bed' },
    { variant: 'status', value: 'draft', label: 'Draft' },
    { variant: 'status', value: 'scheduled', label: 'Scheduled' },
    { variant: 'status', value: 'published', label: 'Published' },
    { variant: 'status', value: 'archived', label: 'Archived' },
    { variant: 'status', value: 'pending_revision', label: 'Pending revision' },
];

const themes = ['light', 'dark'] as const;

const loading = ref(false);
const sendingDemo = ref(false);

function fakeSubmit(): void {
    sendingDemo.value = true;
    setTimeout(() => (sendingDemo.value = false), 2000);
}

function toastWithUndo(): void {
    notify.success('Project archived', {
        action: { label: 'Undo', onClick: () => notify.info('Restored') },
    });
}

const faqs = [
    {
        q: 'Is the project approved by RAJUK?',
        a: 'Yes. The approval number and the plan are listed under Documents on each project page.',
    },
    {
        q: 'Can NRB customers book from abroad?',
        a: 'Yes. Bookings can be made remotely with a power of attorney and an online payment schedule.',
    },
    {
        q: 'When is handover?',
        a: 'Each project shows its planned handover quarter and the latest construction progress.',
    },
];
</script>

<template>
    <div class="bg-background">
        <div class="theme-dark bg-(image:--gradient-dusk)">
            <div
                class="mx-auto max-w-content px-4 py-12 sm:px-6 lg:px-8 xl:px-10"
            >
                <p class="text-overline text-primary uppercase">
                    Phases 0.2–0.3 · local only
                </p>
                <h1 class="mt-2 font-display text-h1">UI kit &amp; tokens</h1>
                <p class="mt-3 max-w-prose text-lead text-muted-foreground">
                    design/foundations.md §2–§5, §13 and CMP-BTN, BADGE, FORM,
                    TABS, ACC, TOAST, EMPTY, SKEL; images and formatters
                    (ARC-016, ARC-017).
                </p>
            </div>
        </div>

        <div
            class="mx-auto flex max-w-content flex-col gap-16 px-4 py-12 sm:px-6 lg:px-8 xl:px-10"
        >
            <!-- Colour scales -->
            <section aria-labelledby="sg-colours" class="flex flex-col gap-6">
                <h2 id="sg-colours" class="font-display text-h2">
                    Colour scales
                </h2>
                <div
                    v-for="scale in scales"
                    :key="scale.name"
                    class="flex flex-col gap-2"
                >
                    <h3 class="font-display text-h4 capitalize">
                        {{ scale.name }}
                    </h3>
                    <ul
                        class="grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-11"
                    >
                        <li
                            v-for="(hex, i) in scale.hex"
                            :key="hex + i"
                            class="flex flex-col gap-1"
                        >
                            <span
                                class="h-12 rounded-md border"
                                :style="{
                                    background: `var(--${scale.name}-${steps[i]})`,
                                }"
                            />
                            <span class="text-xs font-medium">
                                {{ steps[i] }}
                            </span>
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                {{ hex }}
                            </span>
                        </li>
                    </ul>
                </div>
                <div class="flex flex-col gap-2">
                    <h3 class="font-display text-h4">Gradients</h3>
                    <ul class="grid grid-cols-2 gap-2 lg:grid-cols-4">
                        <li
                            v-for="gradient in gradients"
                            :key="gradient"
                            class="flex h-24 items-end rounded-lg p-3 text-sm font-semibold text-white"
                            :style="{
                                background: `var(--gradient-${gradient})`,
                            }"
                        >
                            --gradient-{{ gradient }}
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Semantic tokens -->
            <section aria-labelledby="sg-semantic" class="flex flex-col gap-6">
                <h2 id="sg-semantic" class="font-display text-h2">
                    Semantic tokens
                </h2>
                <div class="grid gap-6 lg:grid-cols-2">
                    <div
                        v-for="theme in themes"
                        :key="theme"
                        :class="[
                            'flex flex-col gap-3 rounded-xl border bg-background p-4 text-foreground sm:p-6',
                            theme === 'dark' && 'theme-dark',
                        ]"
                    >
                        <h3 class="font-display text-h4">
                            {{
                                theme === 'light'
                                    ? 'Light (:root)'
                                    : '.theme-dark'
                            }}
                        </h3>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            <li
                                v-for="[bg, fg] in semanticTokens"
                                :key="bg"
                                class="flex min-h-11 items-center rounded-md border px-3 text-sm font-medium"
                                :style="{
                                    background: `var(--${bg})`,
                                    color: `var(--${fg})`,
                                }"
                            >
                                --{{ bg }}
                            </li>
                        </ul>
                        <ul class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <li
                                v-for="token in lineTokens"
                                :key="token"
                                class="flex flex-col gap-1 text-xs"
                            >
                                <span
                                    class="h-8 rounded-md border-2"
                                    :style="{
                                        borderColor: `var(--${token})`,
                                    }"
                                />
                                --{{ token }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Type -->
            <section aria-labelledby="sg-type" class="flex flex-col gap-6">
                <h2 id="sg-type" class="font-display text-h2">Type scale</h2>
                <dl class="flex flex-col divide-y">
                    <div
                        v-for="item in typeScale"
                        :key="item.token"
                        class="grid gap-1 py-4 md:grid-cols-[10rem_1fr] md:items-baseline md:gap-6"
                    >
                        <dt class="font-mono text-xs text-muted-foreground">
                            {{ item.token }}
                        </dt>
                        <dd :class="item.cls">{{ item.sample }}</dd>
                    </div>
                </dl>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="flex flex-col gap-3">
                        <h3 class="font-display text-h4">Radius</h3>
                        <ul class="flex flex-wrap gap-3">
                            <li
                                v-for="r in radii"
                                :key="r"
                                class="flex size-16 items-center justify-center border-2 border-primary bg-secondary text-xs"
                                :style="{
                                    borderRadius:
                                        r === 'full'
                                            ? '9999px'
                                            : `var(--radius-${r})`,
                                }"
                            >
                                {{ r }}
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col gap-3">
                        <h3 class="font-display text-h4">Shadows</h3>
                        <ul class="flex flex-wrap gap-4">
                            <li
                                v-for="s in shadows"
                                :key="s"
                                class="flex size-16 items-center justify-center rounded-lg bg-card text-xs"
                                :style="{ boxShadow: `var(--shadow-${s})` }"
                            >
                                {{ s }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Buttons -->
            <section aria-labelledby="sg-buttons" class="flex flex-col gap-6">
                <h2 id="sg-buttons" class="font-display text-h2">Buttons</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex items-center gap-3 text-sm">
                        <Switch v-model="loading" />
                        Loading state on every button
                    </label>
                </div>
                <div
                    v-for="variant in buttonVariantList"
                    :key="variant"
                    class="flex flex-col gap-2"
                >
                    <h3 class="font-mono text-xs text-muted-foreground">
                        {{ variant }}
                    </h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <Button :variant="variant" size="sm" :loading="loading">
                            Small
                        </Button>
                        <Button :variant="variant" :loading="loading">
                            Medium
                        </Button>
                        <Button :variant="variant" size="lg" :loading="loading">
                            <template #icon><CalendarCheck /></template>
                            Book a visit
                        </Button>
                        <Button :variant="variant" :loading="loading">
                            View projects
                            <ArrowRight />
                        </Button>
                        <Button :variant="variant" disabled>Disabled</Button>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <h3 class="font-mono text-xs text-muted-foreground">
                        icon (44 px) · icon-sm (36 px, admin only)
                    </h3>
                    <div class="flex flex-wrap items-center gap-3">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    aria-label="Add to shortlist"
                                >
                                    <Heart />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Add to shortlist</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button size="icon" aria-label="Call us">
                                    <Phone />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Call us</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Search"
                                >
                                    <Search />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Search</TooltipContent>
                        </Tooltip>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <h3 class="font-mono text-xs text-muted-foreground">
                        on-media · loading keeps width
                    </h3>
                    <div
                        class="flex flex-wrap items-center gap-3 rounded-xl bg-stone-600 bg-(image:--gradient-hero-left) p-6"
                    >
                        <Button variant="on-media" :loading="loading">
                            Watch the film
                        </Button>
                        <Button variant="on-media" size="lg" :loading="loading">
                            <template #icon><CalendarCheck /></template>
                            Book a visit
                        </Button>
                    </div>
                    <form
                        class="flex max-w-sm flex-col gap-3"
                        @submit.prevent="fakeSubmit"
                    >
                        <Button
                            type="submit"
                            class="w-full sm:w-fit"
                            :loading="sendingDemo"
                        >
                            Send enquiry (2 s loading)
                        </Button>
                    </form>
                </div>
            </section>

            <!-- Badges -->
            <section aria-labelledby="sg-badges" class="flex flex-col gap-6">
                <h2 id="sg-badges" class="font-display text-h2">Badges</h2>
                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="badge in badges"
                        :key="badge.label"
                        :variant="badge.variant"
                        :value="badge.value"
                    >
                        {{ badge.label }}
                    </Badge>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Badge
                        v-for="badge in badges.slice(0, 8)"
                        :key="badge.label"
                        :variant="badge.variant"
                        :value="badge.value"
                        size="md"
                        dot
                    >
                        {{ badge.label }}
                    </Badge>
                </div>
                <div
                    class="flex flex-wrap gap-2 rounded-xl bg-(image:--gradient-brand) p-6"
                >
                    <Badge
                        v-for="badge in badges.slice(0, 8)"
                        :key="badge.label"
                        :variant="badge.variant"
                        :value="badge.value"
                        on-media
                    >
                        {{ badge.label }}
                    </Badge>
                </div>
            </section>

            <!-- Controls -->
            <section aria-labelledby="sg-controls" class="flex flex-col gap-6">
                <h2 id="sg-controls" class="font-display text-h2">
                    Form controls
                </h2>
                <div class="grid gap-6 xl:grid-cols-2">
                    <div
                        v-for="theme in themes"
                        :key="theme"
                        :class="[
                            'flex flex-col gap-6 rounded-xl border bg-card p-4 text-card-foreground sm:p-6',
                            theme === 'dark' && 'theme-dark bg-card',
                        ]"
                    >
                        <h3 class="font-display text-h4">
                            {{ theme === 'light' ? 'Light' : '.theme-dark' }}
                        </h3>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label :for="`${theme}-name`" required>
                                    Full name
                                </Label>
                                <Input
                                    :id="`${theme}-name`"
                                    autocomplete="name"
                                    aria-required="true"
                                    placeholder="Your name"
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label :for="`${theme}-phone`" required>
                                    Phone
                                </Label>
                                <Input
                                    :id="`${theme}-phone`"
                                    type="tel"
                                    autocomplete="tel"
                                    aria-required="true"
                                    aria-invalid="true"
                                    :aria-describedby="`${theme}-phone-error`"
                                    default-value="0171"
                                />
                                <p
                                    :id="`${theme}-phone-error`"
                                    class="flex items-center gap-1.5 text-xs text-destructive"
                                >
                                    <CircleAlert class="size-3.5 shrink-0" />
                                    Enter an 11-digit mobile number.
                                </p>
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label :for="`${theme}-email`">Email</Label>
                                <Input
                                    :id="`${theme}-email`"
                                    type="email"
                                    autocomplete="email"
                                    :aria-describedby="`${theme}-email-help`"
                                />
                                <p
                                    :id="`${theme}-email-help`"
                                    class="text-xs text-muted-foreground"
                                >
                                    We reply within one working day.
                                </p>
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label :for="`${theme}-area`">Area</Label>
                                <Select>
                                    <SelectTrigger :id="`${theme}-area`">
                                        <SelectValue
                                            placeholder="Choose an area"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="bashundhara">
                                            Bashundhara
                                        </SelectItem>
                                        <SelectItem value="uttara"
                                            >Uttara</SelectItem
                                        >
                                        <SelectItem value="mirpur"
                                            >Mirpur</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label :for="`${theme}-disabled`"
                                    >Disabled</Label
                                >
                                <Input
                                    :id="`${theme}-disabled`"
                                    disabled
                                    default-value="Not editable"
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label :for="`${theme}-readonly`"
                                    >Read-only</Label
                                >
                                <Input
                                    :id="`${theme}-readonly`"
                                    readonly
                                    default-value="GC-2026-0012"
                                />
                            </div>
                            <div class="flex flex-col gap-2 md:col-span-2">
                                <Label :for="`${theme}-message`">Message</Label>
                                <Textarea
                                    :id="`${theme}-message`"
                                    placeholder="Tell us what you are looking for"
                                />
                            </div>
                        </div>

                        <fieldset class="flex flex-col gap-3">
                            <legend class="mb-3 text-sm font-medium">
                                Checkboxes
                            </legend>
                            <div class="flex items-center gap-3">
                                <Checkbox :id="`${theme}-cb1`" />
                                <Label :for="`${theme}-cb1`">Unchecked</Label>
                            </div>
                            <div class="flex items-center gap-3">
                                <Checkbox
                                    :id="`${theme}-cb2`"
                                    :default-value="true"
                                />
                                <Label :for="`${theme}-cb2`">Checked</Label>
                            </div>
                            <div class="flex items-center gap-3">
                                <Checkbox
                                    :id="`${theme}-cb3`"
                                    default-value="indeterminate"
                                />
                                <Label :for="`${theme}-cb3`"
                                    >Indeterminate</Label
                                >
                            </div>
                            <div class="flex items-center gap-3">
                                <Checkbox :id="`${theme}-cb4`" disabled />
                                <Label :for="`${theme}-cb4`">Disabled</Label>
                            </div>
                        </fieldset>

                        <fieldset class="flex flex-col gap-3">
                            <legend class="mb-3 text-sm font-medium">
                                Preferred contact time
                            </legend>
                            <RadioGroup default-value="morning">
                                <div class="flex items-center gap-3">
                                    <RadioGroupItem
                                        :id="`${theme}-r1`"
                                        value="morning"
                                    />
                                    <Label :for="`${theme}-r1`">Morning</Label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <RadioGroupItem
                                        :id="`${theme}-r2`"
                                        value="evening"
                                    />
                                    <Label :for="`${theme}-r2`">Evening</Label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <RadioGroupItem
                                        :id="`${theme}-r3`"
                                        value="any"
                                        disabled
                                    />
                                    <Label :for="`${theme}-r3`">Disabled</Label>
                                </div>
                            </RadioGroup>
                        </fieldset>

                        <div class="flex flex-wrap items-center gap-6">
                            <div class="flex items-center gap-3">
                                <Switch
                                    :id="`${theme}-sw1`"
                                    :default-value="true"
                                />
                                <Label :for="`${theme}-sw1`">Newsletter</Label>
                            </div>
                            <div class="flex items-center gap-3">
                                <Switch :id="`${theme}-sw2`" disabled />
                                <Label :for="`${theme}-sw2`">Disabled</Label>
                            </div>
                        </div>

                        <ToggleGroup
                            type="single"
                            variant="outline"
                            default-value="3"
                            aria-label="Bedrooms"
                        >
                            <ToggleGroupItem value="2">2 bed</ToggleGroupItem>
                            <ToggleGroupItem value="3">3 bed</ToggleGroupItem>
                            <ToggleGroupItem value="4">4 bed</ToggleGroupItem>
                        </ToggleGroup>

                        <div class="flex flex-wrap gap-3">
                            <Button>Primary</Button>
                            <Button variant="accent">Enquire</Button>
                            <Button variant="outline">Outline</Button>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button variant="ghost">Popover</Button>
                                </PopoverTrigger>
                                <PopoverContent class="text-sm">
                                    Popovers use the --popover tokens.
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tabs & accordion -->
            <section aria-labelledby="sg-tabs" class="flex flex-col gap-6">
                <h2 id="sg-tabs" class="font-display text-h2">
                    Tabs &amp; accordion
                </h2>
                <Tabs default-value="overview">
                    <TabsList aria-label="Project sections">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="amenities">Amenities</TabsTrigger>
                        <TabsTrigger value="plans">Floor plans</TabsTrigger>
                        <TabsTrigger value="location" disabled>
                            Location
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="overview" class="py-4 text-body">
                        Overview panel — arrow keys move between tabs, Home/End
                        jump.
                    </TabsContent>
                    <TabsContent value="amenities" class="py-4 text-body">
                        Amenities panel.
                    </TabsContent>
                    <TabsContent value="plans" class="py-4 text-body">
                        Floor plans panel.
                    </TabsContent>
                </Tabs>
                <Tabs default-value="all">
                    <TabsList variant="pill" aria-label="Filter projects">
                        <TabsTrigger value="all">All</TabsTrigger>
                        <TabsTrigger value="ongoing">Ongoing</TabsTrigger>
                        <TabsTrigger value="ready">Ready</TabsTrigger>
                    </TabsList>
                </Tabs>
                <Accordion
                    type="single"
                    collapsible
                    default-value="faq-0"
                    class="max-w-narrow"
                >
                    <AccordionItem
                        v-for="(faq, i) in faqs"
                        :key="faq.q"
                        :value="`faq-${i}`"
                    >
                        <AccordionTrigger>{{ faq.q }}</AccordionTrigger>
                        <AccordionContent>{{ faq.a }}</AccordionContent>
                    </AccordionItem>
                </Accordion>
            </section>

            <!-- Toasts -->
            <section aria-labelledby="sg-toasts" class="flex flex-col gap-6">
                <h2 id="sg-toasts" class="font-display text-h2">Toasts</h2>
                <div class="flex flex-wrap gap-3">
                    <Button
                        variant="outline"
                        @click="
                            notify.success('Enquiry sent', {
                                description:
                                    'We will call you within one working day.',
                            })
                        "
                    >
                        Success (4 s)
                    </Button>
                    <Button
                        variant="outline"
                        @click="notify.info('Added to shortlist')"
                    >
                        Info (4 s)
                    </Button>
                    <Button
                        variant="outline"
                        @click="notify.warning('Only 3 units left')"
                    >
                        Warning (6 s)
                    </Button>
                    <Button
                        variant="outline"
                        @click="
                            notify.error('Could not send', {
                                description:
                                    'Check your connection and try again.',
                            })
                        "
                    >
                        Error (stays)
                    </Button>
                    <Button variant="outline" @click="toastWithUndo">
                        With action
                    </Button>
                </div>
            </section>

            <!-- Empty & skeleton -->
            <section aria-labelledby="sg-empty" class="flex flex-col gap-6">
                <h2 id="sg-empty" class="font-display text-h2">
                    Empty states &amp; skeletons
                </h2>
                <div class="grid gap-6 md:grid-cols-3">
                    <EmptyState
                        variant="no-results"
                        class="rounded-xl border"
                        title="No projects match"
                        body="Try removing a filter or widening the price range."
                    >
                        <Button variant="outline">Clear filters</Button>
                    </EmptyState>
                    <EmptyState
                        class="rounded-xl border"
                        title="No enquiries yet"
                        body="New enquiries from the website appear here."
                    >
                        <Button>Add a lead</Button>
                    </EmptyState>
                    <EmptyState
                        variant="error"
                        class="rounded-xl border"
                        title="Could not load"
                        body="Something went wrong on our side."
                    >
                        <Button variant="outline">Retry</Button>
                    </EmptyState>
                </div>
                <div
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4"
                    aria-busy="true"
                >
                    <SkeletonShape shape="project-card" />
                    <SkeletonShape shape="news-card" />
                    <div class="flex flex-col gap-4">
                        <SkeletonShape shape="kpi-card" />
                        <SkeletonShape shape="media-tile" class="w-32" />
                    </div>
                    <div class="rounded-lg border">
                        <SkeletonShape
                            v-for="n in 4"
                            :key="n"
                            shape="table-row"
                        />
                    </div>
                </div>
            </section>

            <!-- Images -->
            <section aria-labelledby="sg-images" class="flex flex-col gap-6">
                <h2 id="sg-images" class="font-display text-h2">Images</h2>
                <EmptyState
                    v-if="!images.hero"
                    class="rounded-xl border"
                    title="No demo images yet"
                    body="Run php artisan demo:images --placeholders, then reload."
                />
                <template v-else>
                    <figure class="flex flex-col gap-2">
                        <div
                            class="relative h-96 overflow-hidden rounded-xl md:h-128"
                        >
                            <ResponsiveImage
                                :image="images.hero"
                                :mobile="images.hero_mobile"
                                preset="hero"
                                priority
                            />
                        </div>
                        <figcaption class="text-body text-muted-foreground">
                            <code>hero</code> · eager ·
                            <code>fetchpriority="high"</code> (the page's only
                            one) · portrait crop below 768 px
                        </figcaption>
                    </figure>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <figure class="flex flex-col gap-2">
                            <ResponsiveImage
                                :image="images.project"
                                preset="project-card"
                                class="rounded-lg"
                            />
                            <figcaption class="text-body text-muted-foreground">
                                <code>project-card</code> 4:3 · lazy
                            </figcaption>
                        </figure>
                        <figure class="flex flex-col gap-2">
                            <ResponsiveImage
                                :image="images.news"
                                preset="news-card"
                                class="rounded-lg"
                            />
                            <figcaption class="text-body text-muted-foreground">
                                <code>news-card</code> 16:10 · lazy
                            </figcaption>
                        </figure>
                        <figure class="flex flex-col gap-2">
                            <ResponsiveImage
                                :image="images.location"
                                preset="segment-tile"
                                class="rounded-lg"
                            />
                            <figcaption class="text-body text-muted-foreground">
                                <code>segment-tile</code> 16:9 → 3:4 at 1024 px
                            </figcaption>
                        </figure>
                        <figure class="flex flex-col gap-2">
                            <ResponsiveImage
                                :image="images.portrait"
                                preset="team-portrait"
                                class="rounded-lg"
                            />
                            <figcaption class="text-body text-muted-foreground">
                                <code>team-portrait</code> 4:5 · focal 50,35
                            </figcaption>
                        </figure>
                        <figure class="flex flex-col gap-2">
                            <ResponsiveImage
                                :image="images.interior"
                                preset="floor-plan"
                                class="rounded-lg"
                            />
                            <figcaption class="text-body text-muted-foreground">
                                <code>floor-plan</code> intrinsic · contain
                            </figcaption>
                        </figure>
                        <figure class="flex flex-col gap-2">
                            <ResponsiveImage
                                :image="null"
                                preset="project-card"
                                class="rounded-lg"
                            />
                            <figcaption class="text-body text-muted-foreground">
                                No image: placeholder only
                            </figcaption>
                        </figure>
                        <figure class="flex flex-col gap-2 sm:col-span-2">
                            <div class="grid grid-cols-4 gap-2">
                                <ResponsiveImage
                                    v-for="(thumb, index) in images.thumbs"
                                    :key="index"
                                    :image="thumb"
                                    preset="gallery-thumb"
                                    class="rounded-md"
                                />
                            </div>
                            <figcaption class="text-body text-muted-foreground">
                                <code>gallery-thumb</code> 1:1 ·
                                <code>sizes="160px"</code>
                                <template v-if="ogImage">
                                    ·
                                    <a
                                        :href="ogImage"
                                        class="text-primary underline"
                                        >og.jpg 1200×630</a
                                    >
                                </template>
                            </figcaption>
                        </figure>
                    </div>
                </template>
            </section>

            <!-- Formatters -->
            <section aria-labelledby="sg-format" class="flex flex-col gap-6">
                <h2 id="sg-format" class="font-display text-h2">Formatters</h2>
                <div class="overflow-x-auto rounded-lg border">
                    <table class="w-full text-left text-body">
                        <thead class="bg-muted">
                            <tr>
                                <th scope="col" class="px-3 py-2">Call</th>
                                <th scope="col" class="px-3 py-2">Spec</th>
                                <th scope="col" class="px-3 py-2">PHP</th>
                                <th scope="col" class="px-3 py-2">TS</th>
                                <th scope="col" class="px-3 py-2">Match</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in formatRows"
                                :key="row.call"
                                class="border-t"
                            >
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
                                        :variant="
                                            row.matches
                                                ? 'lifecycle'
                                                : 'booking'
                                        "
                                        :value="
                                            row.matches ? 'ready' : 'sold_out'
                                        "
                                    >
                                        {{ row.matches ? 'Yes' : 'No' }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</template>
