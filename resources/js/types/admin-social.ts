// ADM-SCR-09 contracts: App\Http\Resources\Admin\{Testimonial,Partner,Faq,TeamMember,Office}RowResource
// and App\Queries\Admin\SocialEditor.
import type { AssetMap, MediaAsset } from '@/types';

export type RowCan = { edit: boolean; delete: boolean };

export type SocialChoice = { value: string; label: string };
export type SocialOption = { value: number; label: string };

export type TestimonialRow = {
    id: number;
    quote: string;
    person_name: string;
    designation: string | null;
    project: { id: number; name: string } | null;
    thumb: string | null;
    is_nrb: boolean;
    is_published: boolean;
    sort_order: number;
    can: RowCan;
};

export type PartnerRow = {
    id: number;
    name: string;
    logo_asset_id: number | null;
    logo: MediaAsset | null;
    url: string | null;
    category: SocialChoice;
    is_published: boolean;
    sort_order: number;
    can: RowCan;
};

export type FaqRow = {
    id: number;
    question: string;
    answer_text: string;
    category: SocialChoice;
    show_on: string[];
    attached: number;
    is_published: boolean;
    sort_order: number;
    can: RowCan;
};

export type TeamMemberRow = {
    id: number;
    name: string;
    role_title: string;
    group: SocialChoice;
    thumb: string | null;
    is_published: boolean;
    sort_order: number;
    can: RowCan;
};

export type OfficeRow = {
    id: number;
    name: string;
    address: string;
    city: string;
    phones: string[];
    email: string | null;
    is_head_office: boolean;
    sort_order: number;
    delete_block: string | null;
    can: RowCan;
};

/** One row of a reorder dialog; `group` splits the list (team group, FAQ topic). */
export type OrderItem = {
    id: number;
    label: string;
    hint?: string | null;
    group?: string;
    published: boolean;
};

export type TestimonialForm = {
    quote: string;
    person_name: string;
    designation: string;
    project_id: number | null;
    photo_asset_id: number | null;
    is_nrb: boolean;
    is_published: boolean;
};

export type TestimonialEditProps = {
    testimonial: { id: number; person_name: string } | null;
    form: TestimonialForm;
    assets: AssetMap;
    options: {
        projects: SocialOption[];
        limits: {
            quote_min: number;
            quote_max: number;
            name: number;
            designation: number;
        };
    };
    lastSaved: string | null;
};

export type TeamMemberForm = {
    name: string;
    role_title: string;
    group: string;
    photo_asset_id: number | null;
    bio: string;
    linkedin_url: string;
    email: string;
    show_email: boolean;
    is_published: boolean;
};

export type TeamMemberEditProps = {
    member: { id: number; name: string } | null;
    form: TeamMemberForm;
    assets: AssetMap;
    options: {
        groups: SocialChoice[];
        limits: { name: number; role: number; bio: number };
    };
    lastSaved: string | null;
};

export type FaqForm = {
    question: string;
    answer: string;
    category: string;
    show_on: string[];
    project_ids: number[];
    location_ids: number[];
    facet_ids: number[];
    is_published: boolean;
};

export type FaqEditProps = {
    faq: { id: number; question: string } | null;
    form: FaqForm;
    options: {
        categories: SocialChoice[];
        placements: SocialChoice[];
        projects: SocialOption[];
        locations: SocialOption[];
        facets: SocialOption[];
        limits: { question: number };
    };
    lastSaved: string | null;
};

export type OfficeHours = { days: string[]; opens: string; closes: string };

export type OfficeForm = {
    name: string;
    address: string;
    city: string;
    postal_code: string;
    phones: string[];
    email: string;
    hours: OfficeHours[];
    latitude: string;
    longitude: string;
    is_head_office: boolean;
};

export type OfficeEditProps = {
    office: { id: number; name: string } | null;
    form: OfficeForm;
    options: { days: string[]; limits: { name: number } };
    lastSaved: string | null;
};
