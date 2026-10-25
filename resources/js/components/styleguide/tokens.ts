// Styleguide reference data (phases 0.2–0.3): mirrors design/foundations.md
// §2–§4 and §13. Developer tooling, not site content.

export const themes = ['light', 'dark'] as const;
export type StyleguideTheme = (typeof themes)[number];

export const steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

export const scales: { name: string; hex: string[] }[] = [
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

export const gradients = ['brand', 'dusk', 'sunrise', 'mesh'] as const;

export const semanticTokens = [
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

export const lineTokens = [
    'border',
    'input',
    'ring',
    'brand-accent-decor',
] as const;

export const typeScale = [
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

export const radii = ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'] as const;
export const shadows = ['xs', 'sm', 'md', 'lg', 'xl'] as const;
