// Props of the contact and thank-you pages (PG-17, PG-22; 7.2).

import type { OfficesBlock, PageHeaderBlock } from '@/types/blocks';
import type { FormSlug } from '@/types/forms';
import type { ProjectCard } from '@/types/site';

/** `Site/Contact` (ContactController). */
export type ContactPageProps = {
    header: PageHeaderBlock;
    offices: OfficesBlock;
    copy: {
        form_heading: string;
        form_intro: string;
        details_heading: string;
        social_heading: string;
        /** Prefilled WhatsApp message (FR-LEAD-013 wording). */
        whatsapp_text: string;
    };
    /** From `?project=`; sent as a hidden field of the form. */
    project: { slug: string; name: string } | null;
    /** Preselected subject (`sales` from a project link). */
    subject: string | null;
};

/** `Site/ThankYou` (ThankYouController). */
export type ThankYouProps = {
    form: FormSlug;
    title: string;
    message: string;
    steps: { title: string; text: string }[];
    /** The project the form was sent for, when known. */
    project: { slug: string; name: string; url: string } | null;
    /** Project forms only. */
    related: { heading: string; projects: ProjectCard[] } | null;
    whatsapp_text: string;
    /** One-request flash from the submit; null on a reload (no `generate_lead`). */
    lead: {
        reference: string;
        lead_type: string;
        project_slug: string | null;
        /** FR-SHORT-004: the shortlist enquiry's projects (one lead each). */
        project_slugs: string[];
        form_location: string | null;
    } | null;
};
