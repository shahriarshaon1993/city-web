import type { SocialNetwork } from '@/components/site/SocialIcon.vue';
import type { SiteInfo } from '@/types/site';

/**
 * UX-12 / FR-LEAD-013 click-to-chat link; `text` is prefilled (PG-04 passes
 * "Hi, I'm interested in {Project}. {url}").
 */
export function whatsappUrl(e164: string, text?: string): string {
    const url = `https://wa.me/${e164.replace(/\D/g, '')}`;

    return text ? `${url}?text=${encodeURIComponent(text)}` : url;
}

const socialNames: Record<SocialNetwork, string> = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    linkedin: 'LinkedIn',
    youtube: 'YouTube',
    x: 'X',
    whatsapp: 'WhatsApp',
};

/** Configured profiles in settings order, with their accessible names. */
export function socialLinks(
    social: SiteInfo['social'],
): { network: SocialNetwork; url: string; label: string }[] {
    return (Object.entries(social) as [SocialNetwork, string | undefined][])
        .filter((entry): entry is [SocialNetwork, string] => Boolean(entry[1]))
        .map(([network, url]) => ({
            network,
            url,
            label: socialNames[network],
        }));
}
