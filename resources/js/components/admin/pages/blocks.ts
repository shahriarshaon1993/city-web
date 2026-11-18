import {
    Award,
    BookOpenText,
    Building2,
    CircleHelp,
    ClipboardList,
    Columns2,
    Footprints,
    GalleryVertical,
    Handshake,
    History,
    Images,
    LayoutGrid,
    LayoutTemplate,
    ListOrdered,
    Map,
    Megaphone,
    MessageSquareQuote,
    Newspaper,
    PanelsTopLeft,
    Quote,
    Rows3,
    Sparkles,
    TextQuote,
    TrendingUp,
    Users,
    Video,
} from '@lucide/vue';
import type { Component } from 'vue';
import type { PageBlockType } from '@/types/blocks';
import type { MediaKind, MenuLink } from '@/types';

// CMP-ADM-BUILDER: one definition per block type (spec/page-builder.md §8.1, the 28 types). It
// drives the add-block gallery, the title of a row and — through `fields` — the form of the block
// drawer. The rules themselves live on the server (App\Support\Blocks\Schemas\*): the form only shows
// the limits before an error happens (VAL-03); the server stays the truth (CMS-PB-003, VAL-08).
// Admin copy: field labels and help are CMS-000a fixed strings, not public content.

export type Data = Record<string, any>;

type Base = {
    key: string;
    label: string;
    helper?: string;
    required?: boolean;
    /** On a select: other fields of the block that go back to blank when this one changes. */
    resets?: string[];
    /** Show the field only while this holds (e.g. slides only for the slider hero). */
    when?: (data: Data) => boolean;
};

/** Where a select takes its choices from: a fixed list, or one of the option lists of the page. */
export type OptionSource =
    | { value: string | number; label: string }[]
    | {
          from:
              | 'segments'
              | 'lifecycles'
              | 'projects'
              | 'areas'
              | 'cities'
              | 'testimonials'
              | 'faq_categories'
              | 'faqs'
              | 'news_categories'
              | 'offices'
              | 'partner_categories'
              | 'icons';
      };

export type Field = Base &
    (
        | { kind: 'text'; max: number; rows?: number; placeholder?: string }
        | { kind: 'html'; minWords?: number; rows?: number }
        | {
              kind: 'number';
              min?: number;
              max?: number;
              step?: number;
              suffix?: string;
          }
        | { kind: 'select'; options: OptionSource; none?: string }
        | { kind: 'switch' }
        | { kind: 'image'; mediaKinds?: MediaKind[]; pickerTitle?: string }
        | { kind: 'asset'; mediaKinds: MediaKind[]; pickerTitle?: string }
        | { kind: 'images'; max: number; pickerTitle?: string }
        | { kind: 'link' }
        | {
              kind: 'ids';
              from: 'projects' | 'testimonials' | 'faqs' | 'offices';
              max: number;
              noun: string;
          }
        | {
              kind: 'list';
              noun: string;
              min: number;
              max: number;
              fields: Field[];
              /** The card title; falls back to "{Noun} n". */
              title?: (item: Data) => string;
              summary?: (item: Data) => string;
              blank: () => Data;
          }
    );

export type Category =
    | 'Openers'
    | 'Words and story'
    | 'Projects and places'
    | 'People and proof'
    | 'Pictures and video'
    | 'News and contact';

/** The wireframe drawn in the gallery tile (a few shapes cover all 28 types). */
export type Wire =
    | 'hero'
    | 'header'
    | 'split'
    | 'cards'
    | 'text'
    | 'band'
    | 'list'
    | 'stats'
    | 'media'
    | 'form'
    | 'quote'
    | 'tabs';

export type BlockDef = {
    type: PageBlockType;
    label: string;
    category: Category;
    description: string;
    icon: Component;
    wire: Wire;
    fields: Field[];
    /** The data of a block just added: valid where it can be, empty where the editor must choose. */
    blank: () => Data;
    /** What a row of the block list shows: the first heading, or null for the type name. */
    title: (data: Data) => string | null;
};

// --- field builders -----------------------------------------------------------------------------
const text = (
    key: string,
    label: string,
    max: number,
    extra: Partial<Base> & { rows?: number; placeholder?: string } = {},
): Field => ({ kind: 'text', key, label, max, ...extra });

const html = (
    key: string,
    label: string,
    extra: Partial<Base> & { minWords?: number; rows?: number } = {},
): Field => ({ kind: 'html', key, label, ...extra });

const choice = (
    key: string,
    label: string,
    options: OptionSource,
    extra: Partial<Base> & { none?: string } = {},
): Field => ({ kind: 'select', key, label, options, ...extra });

const image = (
    key: string,
    label: string,
    extra: Partial<Base> & { mediaKinds?: MediaKind[] } = {},
): Field => ({ kind: 'image', key, label, ...extra });

const link = (
    key: string,
    label: string,
    extra: Partial<Base> = {},
): Field => ({
    kind: 'link',
    key,
    label,
    ...extra,
});

const number = (
    key: string,
    label: string,
    min: number,
    max: number,
    extra: Partial<Base> & { step?: number; suffix?: string } = {},
): Field => ({ kind: 'number', key, label, min, max, ...extra });

const heading = (required = false, max = 80): Field =>
    text('heading', 'Heading', max, {
        required,
        helper: `Shown above the section. Up to ${max} characters.`,
    });

const layoutChoice = choice('layout', 'Layout', [
    { value: 'carousel', label: 'Carousel' },
    { value: 'grid', label: 'Grid' },
]);

const clip = (value: unknown, length = 60): string | null => {
    const textValue = (
        typeof value === 'string' || typeof value === 'number'
            ? String(value)
            : ''
    )
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    if (textValue === '') {
        return null;
    }

    return textValue.length > length
        ? `${textValue.slice(0, length)}…`
        : textValue;
};

/** A link with nothing filled in yet (the picker starts on "Page"). */
export function blankLink(): MenuLink {
    return {
        key: '',
        label: '',
        link_type: 'page',
        linkable_id: null,
        url: null,
        anchor: null,
        new_tab: false,
        link: null,
    };
}

const ICON_LABEL = (name: string): string =>
    name.replace(/-/g, ' ').replace(/^./, (letter) => letter.toUpperCase());

export const iconOptions = (
    icons: string[],
): { value: string; label: string }[] =>
    icons.map((name) => ({ value: name, label: ICON_LABEL(name) }));

// --- the 28 definitions ---------------------------------------------------------------------------
export const BLOCKS: BlockDef[] = [
    {
        type: 'hero',
        label: 'Hero',
        category: 'Openers',
        description:
            'Large opening photo or video with the headline of the page and an optional project search.',
        icon: PanelsTopLeft,
        wire: 'hero',
        title: (data) => clip(data.headline),
        blank: () => ({
            mode: 'single',
            media_type: 'image',
            image: null,
            mobile_image: null,
            video_mp4: null,
            video_webm: null,
            slides: [],
            autoplay: false,
            eyebrow: null,
            headline: '',
            subheadline: null,
            primary_cta: null,
            secondary_cta: null,
            overlay_opacity: 55,
            overlay_style: 'solid',
            text_align: 'left',
            show_search: false,
            height: 'full',
        }),
        fields: [
            choice(
                'mode',
                'Mode',
                [
                    { value: 'single', label: 'One image or video' },
                    { value: 'slider', label: 'Slider (2 to 5 slides)' },
                ],
                { required: true },
            ),
            choice(
                'media_type',
                'Media',
                [
                    { value: 'image', label: 'Image' },
                    {
                        value: 'video',
                        label: 'Video (the image is its poster)',
                    },
                ],
                { required: true, when: (d) => d.mode !== 'slider' },
            ),
            image('image', 'Hero image', {
                required: true,
                helper: 'At least 1920 px wide. For a video, this is the poster shown before it plays.',
                when: (d) => d.mode !== 'slider',
            }),
            image('mobile_image', 'Mobile image', {
                helper: 'Optional portrait crop for phones, at least 1080 px wide.',
                when: (d) => d.mode !== 'slider',
            }),
            {
                kind: 'asset',
                key: 'video_mp4',
                label: 'MP4 video',
                mediaKinds: ['video'],
                required: true,
                helper: 'Up to 50 MB. It plays muted and looped, so no sound is needed.',
                when: (d) => d.mode !== 'slider' && d.media_type === 'video',
            },
            {
                kind: 'asset',
                key: 'video_webm',
                label: 'WebM video',
                mediaKinds: ['video'],
                helper: 'Optional smaller copy for browsers that support it.',
                when: (d) => d.mode !== 'slider' && d.media_type === 'video',
            },
            {
                kind: 'list',
                key: 'slides',
                label: 'Slides',
                noun: 'slide',
                min: 2,
                max: 5,
                when: (d) => d.mode === 'slider',
                helper: 'Two to five slides. The first one is what loads first.',
                blank: () => ({ image: null, mobile_image: null }),
                fields: [
                    image('image', 'Slide image', {
                        required: true,
                        helper: 'At least 1920 px wide.',
                    }),
                    image('mobile_image', 'Mobile image', {
                        helper: 'Optional portrait crop, at least 1080 px wide.',
                    }),
                ],
            },
            {
                kind: 'switch',
                key: 'autoplay',
                label: 'Change slides by itself',
                helper: 'Stops on hover, focus and for visitors who prefer less motion.',
                when: (d) => d.mode === 'slider',
            },
            text('eyebrow', 'Small line above', 40, {
                helper: 'Optional, e.g. a tagline. Up to 40 characters.',
            }),
            text('headline', 'Headline', 80, {
                required: true,
                helper: 'This becomes the heading of the page (H1). Up to 80 characters.',
            }),
            text('subheadline', 'Subheadline', 160, {
                rows: 2,
                helper: 'One supporting sentence. Up to 160 characters.',
            }),
            link('primary_cta', 'Main button'),
            link('secondary_cta', 'Second button'),
            number('overlay_opacity', 'Overlay strength', 35, 80, {
                step: 5,
                suffix: '%',
                required: true,
                helper: 'Darkens the photo so white text stays readable. 35 to 80, in steps of 5.',
            }),
            choice(
                'overlay_style',
                'Overlay style',
                [
                    { value: 'solid', label: 'Even' },
                    {
                        value: 'gradient-bottom',
                        label: 'Fades from the bottom',
                    },
                    { value: 'gradient-left', label: 'Fades from the left' },
                ],
                { required: true },
            ),
            choice(
                'text_align',
                'Text alignment',
                [
                    { value: 'left', label: 'Left' },
                    { value: 'center', label: 'Centred' },
                ],
                { required: true },
            ),
            {
                kind: 'switch',
                key: 'show_search',
                label: 'Show the project search',
                helper: 'The search box under the headline.',
            },
            choice(
                'height',
                'Height',
                [
                    { value: 'full', label: 'Full screen' },
                    { value: 'large', label: 'Large' },
                    { value: 'medium', label: 'Medium' },
                ],
                { required: true },
            ),
        ],
    },
    {
        type: 'page_header',
        label: 'Page header',
        category: 'Openers',
        description:
            'The title of an inner page, with a short intro, an optional photo and the breadcrumb trail.',
        icon: LayoutTemplate,
        wire: 'header',
        title: (data) => clip(data.title),
        blank: () => ({
            title: '',
            intro: null,
            image: null,
            breadcrumb: true,
        }),
        fields: [
            text('title', 'Title', 80, {
                required: true,
                helper: 'This becomes the heading of the page (H1). Up to 80 characters.',
            }),
            text('intro', 'Intro', 400, {
                rows: 3,
                helper: 'One or two sentences under the title.',
            }),
            image('image', 'Background image', {
                helper: 'Optional. With a photo the header sits over it and the menu turns transparent.',
            }),
            {
                kind: 'switch',
                key: 'breadcrumb',
                label: 'Show the breadcrumb trail',
            },
        ],
    },
    {
        type: 'intro_statement',
        label: 'Intro statement',
        category: 'Words and story',
        description:
            'A large statement with a short paragraph, an optional button and a side photo.',
        icon: TextQuote,
        wire: 'split',
        title: (data) => clip(data.heading),
        blank: () => ({
            eyebrow: null,
            heading: '',
            body: null,
            cta: null,
            image: null,
        }),
        fields: [
            text('eyebrow', 'Small line above', 40),
            text('heading', 'Heading', 120, {
                required: true,
                helper: 'Up to 120 characters.',
            }),
            html('body', 'Text', { helper: 'Up to 120 words.', rows: 5 }),
            link('cta', 'Button'),
            image('image', 'Side image'),
        ],
    },
    {
        type: 'featured_projects',
        label: 'Featured projects',
        category: 'Projects and places',
        description:
            'A carousel or grid of project cards: featured, hand-picked, by type or by status.',
        icon: Sparkles,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: '',
            source: 'featured',
            manual_ids: [],
            segment: null,
            status: null,
            limit: 6,
            layout: 'carousel',
        }),
        fields: [
            heading(true),
            choice(
                'source',
                'Which projects',
                [
                    { value: 'featured', label: 'The featured projects' },
                    { value: 'manual', label: 'Projects I choose' },
                    { value: 'segment', label: 'All of one type' },
                    { value: 'status', label: 'All with one status' },
                ],
                { required: true },
            ),
            {
                kind: 'ids',
                key: 'manual_ids',
                label: 'Projects',
                from: 'projects',
                max: 12,
                noun: 'project',
                when: (d) => d.source === 'manual',
                required: true,
            },
            choice(
                'segment',
                'Project type',
                { from: 'segments' },
                { required: true, when: (d) => d.source === 'segment' },
            ),
            choice(
                'status',
                'Project status',
                { from: 'lifecycles' },
                { required: true, when: (d) => d.source === 'status' },
            ),
            number('limit', 'How many', 3, 12, {
                required: true,
                helper: '3 to 12.',
            }),
            layoutChoice,
        ],
    },
    {
        type: 'project_grid',
        label: 'Project grid',
        category: 'Projects and places',
        description:
            'A grid of project cards filtered by type, status or area, with a "View all" link.',
        icon: LayoutGrid,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            segment: null,
            status: null,
            area_id: null,
            limit: 6,
            show_view_all: true,
        }),
        fields: [
            heading(),
            choice(
                'segment',
                'Project type',
                { from: 'segments' },
                { none: 'Any type' },
            ),
            choice(
                'status',
                'Project status',
                { from: 'lifecycles' },
                { none: 'Any status' },
            ),
            choice('area_id', 'Area', { from: 'areas' }, { none: 'Any area' }),
            number('limit', 'How many', 3, 24, {
                required: true,
                helper: '3 to 24.',
            }),
            {
                kind: 'switch',
                key: 'show_view_all',
                label: 'Show a "View all" link',
            },
        ],
    },
    {
        type: 'segment_tiles',
        label: 'Segment tiles',
        category: 'Projects and places',
        description:
            'Two to four large photo tiles, one per project type, each linking to its listing.',
        icon: Rows3,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            tiles: [
                {
                    segment: 'residential',
                    image: null,
                    label: null,
                    description: null,
                },
                {
                    segment: 'commercial',
                    image: null,
                    label: null,
                    description: null,
                },
            ],
        }),
        fields: [
            heading(),
            {
                kind: 'list',
                key: 'tiles',
                label: 'Tiles',
                noun: 'tile',
                min: 2,
                max: 4,
                required: true,
                blank: () => ({
                    segment: null,
                    image: null,
                    label: null,
                    description: null,
                }),
                title: (item) => clip(item.label) ?? 'Tile',
                fields: [
                    choice(
                        'segment',
                        'Project type',
                        { from: 'segments' },
                        { required: true },
                    ),
                    image('image', 'Image', {
                        required: true,
                        helper: 'At least 1200 px wide.',
                    }),
                    text('label', 'Label', 40, {
                        helper: 'Leave empty to use the name of the type.',
                    }),
                    text('description', 'Description', 120),
                ],
            },
        ],
    },
    {
        type: 'stats_counters',
        label: 'Stats counters',
        category: 'Words and story',
        description:
            'Two to six big numbers that count up, such as projects delivered or families served.',
        icon: TrendingUp,
        wire: 'stats',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            items: [
                { value: 0, suffix: '+', label: '', decimals: 0 },
                { value: 0, suffix: '+', label: '', decimals: 0 },
            ],
        }),
        fields: [
            heading(),
            {
                kind: 'list',
                key: 'items',
                label: 'Figures',
                noun: 'figure',
                min: 2,
                max: 6,
                required: true,
                blank: () => ({
                    value: 0,
                    suffix: null,
                    label: '',
                    decimals: 0,
                }),
                title: (item) => clip(item.label) ?? 'Figure',
                summary: (item) => `${item.value ?? ''}${item.suffix ?? ''}`,
                fields: [
                    number('value', 'Number', 0, 1_000_000_000, {
                        required: true,
                    }),
                    text('suffix', 'Suffix', 6, {
                        helper: 'Shown after the number, e.g. + or M sft.',
                    }),
                    text('label', 'Label', 40, { required: true }),
                    choice(
                        'decimals',
                        'Decimals',
                        [
                            { value: 0, label: 'None' },
                            { value: 1, label: 'One' },
                        ],
                        { required: true },
                    ),
                ],
            },
        ],
    },
    {
        type: 'progress_highlight',
        label: 'Construction progress',
        category: 'Projects and places',
        description:
            'The latest construction stage and photos of up to three ongoing projects.',
        icon: Footprints,
        wire: 'media',
        title: (data) => clip(data.heading),
        blank: () => ({ heading: '', project_ids: [] }),
        fields: [
            heading(true),
            {
                kind: 'ids',
                key: 'project_ids',
                label: 'Projects',
                from: 'projects',
                max: 3,
                noun: 'project',
                required: true,
                helper: 'One to three projects that have at least one construction update.',
            },
        ],
    },
    {
        type: 'testimonials',
        label: 'Testimonials',
        category: 'People and proof',
        description: 'Quotes from buyers, as a carousel or a grid.',
        icon: MessageSquareQuote,
        wire: 'quote',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            source: 'all',
            ids: [],
            layout: 'carousel',
            limit: 6,
        }),
        fields: [
            heading(),
            choice(
                'source',
                'Which testimonials',
                [
                    { value: 'all', label: 'All published' },
                    {
                        value: 'nrb',
                        label: 'Only from non-resident Bangladeshis',
                    },
                    { value: 'project', label: 'About chosen projects' },
                    { value: 'manual', label: 'Ones I choose' },
                ],
                { required: true, resets: ['ids'] },
            ),
            {
                kind: 'ids',
                key: 'ids',
                label: 'Projects',
                from: 'projects',
                max: 12,
                noun: 'project',
                required: true,
                when: (d) => d.source === 'project',
            },
            {
                kind: 'ids',
                key: 'ids',
                label: 'Testimonials',
                from: 'testimonials',
                max: 12,
                noun: 'testimonial',
                required: true,
                when: (d) => d.source === 'manual',
            },
            number('limit', 'How many', 3, 12, { required: true }),
            layoutChoice,
        ],
    },
    {
        type: 'partners_marquee',
        label: 'Partners strip',
        category: 'People and proof',
        description:
            'A slowly scrolling row of partner logos: banks, suppliers and consultants.',
        icon: Handshake,
        wire: 'band',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            category: null,
            speed: 'normal',
            direction: 'left',
        }),
        fields: [
            heading(),
            choice(
                'category',
                'Partners',
                { from: 'partner_categories' },
                {
                    none: 'All partners',
                    helper: 'The strip needs at least 4 partners.',
                },
            ),
            choice(
                'speed',
                'Speed',
                [
                    { value: 'slow', label: 'Slow (60 s a loop)' },
                    { value: 'normal', label: 'Normal (40 s a loop)' },
                ],
                { required: true },
            ),
            choice(
                'direction',
                'Direction',
                [
                    { value: 'left', label: 'To the left' },
                    { value: 'right', label: 'To the right' },
                ],
                { required: true },
            ),
        ],
    },
    {
        type: 'latest_news',
        label: 'Latest news',
        category: 'News and contact',
        description:
            'The newest articles as cards, optionally from one category.',
        icon: Newspaper,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({ heading: null, category: null, limit: 3 }),
        fields: [
            heading(),
            choice(
                'category',
                'Category',
                { from: 'news_categories' },
                { none: 'All categories' },
            ),
            choice(
                'limit',
                'How many',
                [
                    { value: 3, label: '3 articles' },
                    { value: 6, label: '6 articles' },
                ],
                { required: true },
            ),
        ],
    },
    {
        type: 'cta_band',
        label: 'Call-to-action band',
        category: 'News and contact',
        description:
            'A full-width band with one clear action, on brand colour, gradient or a photo.',
        icon: Megaphone,
        wire: 'band',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: '',
            text: null,
            primary_cta: blankLink(),
            secondary_cta: null,
            style: 'brand',
            image: null,
        }),
        fields: [
            text('heading', 'Heading', 70, {
                required: true,
                helper: 'Up to 70 characters.',
            }),
            text('text', 'Text', 200, { rows: 2 }),
            link('primary_cta', 'Main button', { required: true }),
            link('secondary_cta', 'Second button'),
            choice(
                'style',
                'Style',
                [
                    { value: 'brand', label: 'Brand colour' },
                    { value: 'gradient', label: 'Gradient' },
                    { value: 'image', label: 'Over a photo' },
                ],
                { required: true },
            ),
            image('image', 'Photo', {
                required: true,
                when: (d) => d.style === 'image',
            }),
        ],
    },
    {
        type: 'rich_text',
        label: 'Rich text',
        category: 'Words and story',
        description:
            'Formatted text: headings, paragraphs, lists and links. Good for long articles and policies.',
        icon: BookOpenText,
        wire: 'text',
        title: (data) => clip(data.body),
        blank: () => ({ body: '', width: 'narrow' }),
        fields: [
            html('body', 'Text', { required: true, rows: 10 }),
            choice(
                'width',
                'Width',
                [
                    { value: 'narrow', label: 'Narrow column (easy to read)' },
                    { value: 'wide', label: 'Wide' },
                ],
                { required: true },
            ),
        ],
    },
    {
        type: 'image_text',
        label: 'Image and text',
        category: 'Words and story',
        description:
            'A photo beside a heading and text, with an optional button. Alternate the side down the page.',
        icon: Columns2,
        wire: 'split',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: '',
            body: '',
            image: null,
            image_side: 'left',
            cta: null,
            reveal: 'mask',
        }),
        fields: [
            text('heading', 'Heading', 80, { required: true }),
            html('body', 'Text', { required: true, rows: 6 }),
            image('image', 'Image', { required: true }),
            choice(
                'image_side',
                'Image side',
                [
                    { value: 'left', label: 'Left' },
                    { value: 'right', label: 'Right' },
                ],
                { required: true },
            ),
            link('cta', 'Button'),
            choice(
                'reveal',
                'Image reveal',
                [
                    { value: 'mask', label: 'Unveils as it scrolls into view' },
                    { value: 'none', label: 'No effect' },
                ],
                { required: true },
            ),
        ],
    },
    {
        type: 'feature_cards',
        label: 'Feature columns',
        category: 'Words and story',
        description:
            'Two to twelve short points, each with an icon or photo, a title and a line of text.',
        icon: LayoutGrid,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            columns: 3,
            tilt: false,
            items: [
                {
                    icon: 'award',
                    image: null,
                    title: '',
                    text: null,
                    link: null,
                },
                {
                    icon: 'award',
                    image: null,
                    title: '',
                    text: null,
                    link: null,
                },
            ],
        }),
        fields: [
            heading(),
            choice(
                'columns',
                'Columns',
                [
                    { value: 2, label: '2' },
                    { value: 3, label: '3' },
                    { value: 4, label: '4' },
                ],
                { required: true },
            ),
            {
                kind: 'switch',
                key: 'tilt',
                label: 'Tilt the cards towards the pointer',
            },
            {
                kind: 'list',
                key: 'items',
                label: 'Points',
                noun: 'point',
                min: 2,
                max: 12,
                required: true,
                blank: () => ({
                    icon: 'award',
                    image: null,
                    title: '',
                    text: null,
                    link: null,
                }),
                title: (item) => clip(item.title) ?? 'Point',
                fields: [
                    choice(
                        'icon',
                        'Icon',
                        { from: 'icons' },
                        {
                            none: 'No icon',
                            helper: 'Choose an icon or a photo.',
                        },
                    ),
                    image('image', 'Photo', {
                        helper: 'Used instead of the icon when set.',
                    }),
                    text('title', 'Title', 60, { required: true }),
                    text('text', 'Text', 240, { rows: 3 }),
                    link('link', 'Link'),
                ],
            },
        ],
    },
    {
        type: 'process_steps',
        label: 'Process steps',
        category: 'Words and story',
        description:
            'Numbered steps that explain how something works, such as how to buy or how to sell land.',
        icon: ListOrdered,
        wire: 'list',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: '',
            steps: [
                { title: '', text: '' },
                { title: '', text: '' },
            ],
        }),
        fields: [
            heading(true),
            {
                kind: 'list',
                key: 'steps',
                label: 'Steps',
                noun: 'step',
                min: 2,
                max: 8,
                required: true,
                blank: () => ({ title: '', text: '' }),
                title: (item) => clip(item.title) ?? 'Step',
                fields: [
                    text('title', 'Title', 60, { required: true }),
                    text('text', 'Text', 300, { required: true, rows: 3 }),
                ],
            },
        ],
    },
    {
        type: 'timeline',
        label: 'Timeline',
        category: 'Words and story',
        description: 'The company story year by year, with optional photos.',
        icon: History,
        wire: 'list',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: '',
            items: [
                { year: 2000, title: '', text: null, image: null },
                { year: 2010, title: '', text: null, image: null },
            ],
        }),
        fields: [
            heading(true),
            {
                kind: 'list',
                key: 'items',
                label: 'Milestones',
                noun: 'milestone',
                min: 2,
                max: 30,
                required: true,
                blank: () => ({
                    year: new Date().getFullYear(),
                    title: '',
                    text: null,
                    image: null,
                }),
                title: (item) =>
                    `${item.year ?? ''} ${clip(item.title) ?? ''}`.trim() ||
                    'Milestone',
                fields: [
                    number('year', 'Year', 1950, 2100, { required: true }),
                    text('title', 'Title', 80, { required: true }),
                    text('text', 'Text', 300, { rows: 3 }),
                    image('image', 'Photo'),
                ],
            },
        ],
    },
    {
        type: 'tabbed_pillars',
        label: 'Tabbed pillars',
        category: 'Words and story',
        description:
            'Two to five tabs, each a short story with a photo: values, services or sectors.',
        icon: PanelsTopLeft,
        wire: 'tabs',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            tabs: [
                { label: '', title: '', text: '', image: null },
                { label: '', title: '', text: '', image: null },
            ],
        }),
        fields: [
            heading(),
            {
                kind: 'list',
                key: 'tabs',
                label: 'Tabs',
                noun: 'tab',
                min: 2,
                max: 5,
                required: true,
                blank: () => ({ label: '', title: '', text: '', image: null }),
                title: (item) => clip(item.label) ?? 'Tab',
                fields: [
                    text('label', 'Tab name', 24, { required: true }),
                    text('title', 'Title', 80, { required: true }),
                    text('text', 'Text', 500, { required: true, rows: 4 }),
                    image('image', 'Photo'),
                ],
            },
        ],
    },
    {
        type: 'team_grid',
        label: 'Team grid',
        category: 'People and proof',
        description:
            'Portraits and names of the leadership, management or the wider team.',
        icon: Users,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({ heading: null, group: 'leadership' }),
        fields: [
            heading(),
            choice(
                'group',
                'Group',
                [
                    { value: 'leadership', label: 'Leadership' },
                    { value: 'management', label: 'Management' },
                    { value: 'team', label: 'Team' },
                ],
                { required: true },
            ),
        ],
    },
    {
        type: 'faq',
        label: 'FAQ',
        category: 'People and proof',
        description:
            'Questions and answers in an accordion, from a category or picked one by one.',
        icon: CircleHelp,
        wire: 'list',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            source: 'category',
            category: null,
            ids: [],
        }),
        fields: [
            heading(),
            choice(
                'source',
                'Which questions',
                [
                    { value: 'category', label: 'A whole category' },
                    { value: 'manual', label: 'Ones I choose' },
                ],
                { required: true },
            ),
            choice(
                'category',
                'Category',
                { from: 'faq_categories' },
                { required: true, when: (d) => d.source !== 'manual' },
            ),
            {
                kind: 'ids',
                key: 'ids',
                label: 'Questions',
                from: 'faqs',
                max: 30,
                noun: 'question',
                required: true,
                when: (d) => d.source === 'manual',
            },
        ],
    },
    {
        type: 'gallery',
        label: 'Gallery',
        category: 'Pictures and video',
        description:
            'Two to forty photos in a grid or masonry layout, opening in a lightbox.',
        icon: Images,
        wire: 'media',
        title: (data) => clip(data.heading),
        blank: () => ({ heading: null, layout: 'grid', images: [] }),
        fields: [
            heading(),
            choice(
                'layout',
                'Layout',
                [
                    { value: 'grid', label: 'Even grid' },
                    { value: 'masonry', label: 'Masonry' },
                ],
                { required: true },
            ),
            {
                kind: 'images',
                key: 'images',
                label: 'Photos',
                max: 40,
                required: true,
                helper: 'Two to forty photos. Each needs alt text in the library, or must be marked decorative.',
            },
        ],
    },
    {
        type: 'video',
        label: 'Video',
        category: 'Pictures and video',
        description:
            'A YouTube video or an uploaded MP4 with a poster image and optional captions.',
        icon: Video,
        wire: 'media',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            source: 'youtube',
            url: null,
            video_asset: null,
            poster: null,
            captions: null,
            caption: null,
        }),
        fields: [
            heading(),
            choice(
                'source',
                'Video from',
                [
                    { value: 'youtube', label: 'YouTube' },
                    { value: 'upload', label: 'A file from the library' },
                ],
                { required: true },
            ),
            text('url', 'YouTube address', 200, {
                required: true,
                placeholder: 'https://www.youtube.com/watch?v=…',
                when: (d) => d.source === 'youtube',
            }),
            {
                kind: 'asset',
                key: 'video_asset',
                label: 'MP4 file',
                mediaKinds: ['video'],
                required: true,
                helper: 'Up to 50 MB.',
                when: (d) => d.source === 'upload',
            },
            image('poster', 'Poster image', {
                required: true,
                helper: 'Shown before the video plays. The video loads only when a visitor presses play.',
            }),
            {
                kind: 'asset',
                key: 'captions',
                label: 'Captions file',
                mediaKinds: ['document'],
                helper: 'A WebVTT file. Needed for uploaded videos with speech.',
                when: (d) => d.source === 'upload',
            },
            text('caption', 'Caption', 200),
        ],
    },
    {
        type: 'awards',
        label: 'Awards',
        category: 'People and proof',
        description:
            'Awards and certificates with their logo, title, issuer and year.',
        icon: Award,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: null,
            items: [{ image: null, title: '', issuer: null, year: null }],
        }),
        fields: [
            heading(),
            {
                kind: 'list',
                key: 'items',
                label: 'Awards',
                noun: 'award',
                min: 1,
                max: 24,
                required: true,
                blank: () => ({
                    image: null,
                    title: '',
                    issuer: null,
                    year: null,
                }),
                title: (item) => clip(item.title) ?? 'Award',
                fields: [
                    image('image', 'Image', { required: true }),
                    text('title', 'Title', 80, { required: true }),
                    text('issuer', 'Awarded by', 80),
                    number('year', 'Year', 1950, 2100),
                ],
            },
        ],
    },
    {
        type: 'locations_grid',
        label: 'Locations grid',
        category: 'Projects and places',
        description:
            'Cards for the areas Gemcon City builds in, optionally from one city.',
        icon: Map,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({ heading: null, city: null, limit: 6 }),
        fields: [
            heading(),
            choice('city', 'City', { from: 'cities' }, { none: 'All cities' }),
            number('limit', 'How many', 1, 24, { required: true }),
        ],
    },
    {
        type: 'offices',
        label: 'Offices',
        category: 'News and contact',
        description:
            'Addresses, phone numbers and opening hours of the offices.',
        icon: Building2,
        wire: 'cards',
        title: (data) => clip(data.heading),
        blank: () => ({ heading: null, office_ids: [] }),
        fields: [
            heading(),
            {
                kind: 'ids',
                key: 'office_ids',
                label: 'Offices',
                from: 'offices',
                max: 20,
                noun: 'office',
                helper: 'Leave empty to show all offices.',
            },
        ],
    },
    {
        type: 'form',
        label: 'Enquiry form',
        category: 'News and contact',
        description:
            'A lead form: contact, callback request, landowner or non-resident Bangladeshi enquiry.',
        icon: ClipboardList,
        wire: 'form',
        title: (data) => clip(data.heading),
        blank: () => ({
            heading: '',
            intro: null,
            form_type: 'contact',
            side: null,
        }),
        fields: [
            text('heading', 'Heading', 80, { required: true }),
            text('intro', 'Intro', 300, { rows: 3 }),
            choice(
                'form_type',
                'Form',
                [
                    { value: 'contact', label: 'Contact' },
                    { value: 'callback', label: 'Request a callback' },
                    { value: 'landowner', label: 'Landowner enquiry' },
                    { value: 'nrb', label: 'Non-resident Bangladeshi enquiry' },
                ],
                { required: true },
            ),
            html('side', 'Text beside the form', { rows: 5 }),
        ],
    },
    {
        type: 'quote',
        label: 'Quote',
        category: 'Words and story',
        description:
            'One large pull quote with the name, role and photo of the person.',
        icon: Quote,
        wire: 'quote',
        title: (data) => clip(data.quote),
        blank: () => ({ quote: '', person: '', role: null, photo: null }),
        fields: [
            text('quote', 'Quote', 400, { required: true, rows: 4 }),
            text('person', 'Name', 80, { required: true }),
            text('role', 'Role', 80),
            image('photo', 'Photo'),
        ],
    },
    {
        type: 'parallax_band',
        label: 'Photo band',
        category: 'Pictures and video',
        description:
            'A tall full-width photo with an optional line of text, drifting slowly as you scroll.',
        icon: GalleryVertical,
        wire: 'media',
        title: (data) => clip(data.heading),
        blank: () => ({
            image: null,
            heading: null,
            text: null,
            height: 'md',
            parallax: true,
        }),
        fields: [
            image('image', 'Image', {
                required: true,
                helper: 'At least 1920 px wide.',
            }),
            text('heading', 'Heading', 80),
            text('text', 'Text', 200, { rows: 2 }),
            choice(
                'height',
                'Height',
                [
                    { value: 'md', label: 'Medium (60% of the screen)' },
                    { value: 'lg', label: 'Large (80% of the screen)' },
                ],
                { required: true },
            ),
            {
                kind: 'switch',
                key: 'parallax',
                label: 'Move slowly while scrolling',
                helper: 'Switched off automatically for visitors who prefer less motion.',
            },
        ],
    },
];

export const BLOCK_BY_TYPE: Record<PageBlockType, BlockDef> =
    Object.fromEntries(BLOCKS.map((def) => [def.type, def])) as Record<
        PageBlockType,
        BlockDef
    >;

export const CATEGORY_ORDER: Category[] = [
    'Openers',
    'Words and story',
    'Projects and places',
    'People and proof',
    'Pictures and video',
    'News and contact',
];
