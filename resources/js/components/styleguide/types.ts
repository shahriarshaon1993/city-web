import type { MediaImage } from '@/types/site';

/** StyleguideController `images` prop (the first images of the media library). */
export type StyleguideImages = {
    hero: MediaImage | null;
    hero_mobile: MediaImage | null;
    project: MediaImage | null;
    news: MediaImage | null;
    location: MediaImage | null;
    portrait: MediaImage | null;
    interior: MediaImage | null;
    thumbs: (MediaImage | null)[];
};

/** One formatter call, run by PHP on the server and by TS in the table. */
export type FormatSample = {
    fn:
        | 'formatBdt'
        | 'formatBdtCompact'
        | 'formatBdtRange'
        | 'formatSft'
        | 'formatSftRange'
        | 'formatKatha'
        | 'formatDate'
        | 'formatMonth'
        | 'formatPhone';
    args: (number | string | boolean | null)[];
    php: string;
    expected: string | null;
};
