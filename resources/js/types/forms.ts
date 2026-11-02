// Lead-form contract (7.1): the `forms` shared prop is App\Support\Forms\FormOptions::shared().

/** URL slug of a lead type: `POST /forms/{slug}`, `/thank-you/{slug}`. */
export type FormSlug =
    | 'project-inquiry'
    | 'site-visit'
    | 'brochure-download'
    | 'callback'
    | 'contact'
    | 'landowner'
    | 'nrb';

export type FormOption = {
    value: string;
    label: string;
};

export type FormCountry = {
    /** ISO 3166-1 alpha-2. */
    code: string;
    name: string;
    /** Dial code without `+`. */
    dial: string;
};

export type FormChoices = {
    timeline: FormOption[];
    purpose: FormOption[];
    /** Also the site-visit time slots and the NRB contact times. */
    contact_time: FormOption[];
    subject: FormOption[];
    ownership: FormOption[];
    facing: FormOption[];
    budget: FormOption[];
    /** Area names for the landowner suggestions; `label` is the city. */
    areas: FormOption[];
};

export type FormsShared = {
    choices: FormChoices;
    countries: FormCountry[];
    default_country: string;
    limits: {
        message_max: number;
        contact_message_min: number;
        files: {
            max: number;
            max_mb: number;
            mimes: string[];
            /** e.g. "PDF, JPG or PNG". */
            label: string;
        };
        visitors: { min: number; max: number };
    };
    /** Site-visit window in `timezone` (tomorrow … +60 days), ISO dates. */
    visit_window: { min: string; max: string; timezone: string };
    /** Title and intro of the footer's "Request a call back" dialog. */
    callback_dialog: { title: string; text: string };
    /** FR-LEAD-014: consent text per lead type (underscore key). */
    consent: Record<string, string>;
    privacy_url: string;
};

/** The project a form was opened for (prefilled, hidden field). */
export type FormProject = {
    slug: string;
    name: string;
    /** Unit types offered in the enquiry form's select. */
    unit_types?: { id: number; name: string }[];
};

/** Where on the site the form sits (`form_location`, SEO-ANA-004). */
export type FormLocation =
    | 'enquiry_dialog'
    | 'visit_dialog'
    | 'brochure_dialog'
    | 'page_block'
    | 'contact_page'
    | 'footer';
