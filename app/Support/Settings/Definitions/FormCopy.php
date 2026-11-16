<?php

namespace App\Support\Settings\Definitions;

/**
 * Default copy of Settings → Forms (placeholder text for the client to confirm, DEC-63). The
 * wording is final-looking on purpose, so the site reads naturally before anything is edited.
 */
final class FormCopy
{
    /**
     * Every form that records consent (FR-LEAD-014), with the name the admin shows.
     *
     * @var array<string, string>
     */
    public const array FORMS = [
        'project_inquiry' => 'Project enquiry',
        'site_visit' => 'Site visit',
        'brochure_download' => 'Brochure download',
        'callback' => 'Call back',
        'contact' => 'Contact message',
        'landowner' => 'Landowner',
        'nrb' => 'NRB desk',
        'job_application' => 'Job application',
        'newsletter' => 'Newsletter',
    ];

    /** @var array<string, string> */
    public const array CONSENT = [
        'project_inquiry' => 'I agree that Gemcon City Limited may contact me by phone, WhatsApp or email about this project, as described in the privacy policy.',
        'site_visit' => 'I agree that Gemcon City Limited may contact me to confirm my site visit, as described in the privacy policy.',
        'brochure_download' => 'I agree that Gemcon City Limited may contact me about this project, as described in the privacy policy.',
        'callback' => 'I agree that Gemcon City Limited may call me back on this number, as described in the privacy policy.',
        'contact' => 'I agree that Gemcon City Limited may reply to my message by phone, WhatsApp or email, as described in the privacy policy.',
        'landowner' => 'I agree that Gemcon City Limited may contact me about my land and keep the documents I attach, as described in the privacy policy.',
        'nrb' => 'I agree that Gemcon City Limited may contact me by phone, WhatsApp or email at the times I chose, as described in the privacy policy.',
        'job_application' => 'I agree that Gemcon City Limited may keep my application and CV for twelve months and contact me about roles, as described in the privacy policy.',
        'newsletter' => 'I agree to receive project updates from Gemcon City Limited by email, as described in the privacy policy.',
    ];

    /**
     * Thank-you page copy per lead form (PG-22).
     *
     * @var array<string, array{title: string, message: string, steps: list<array{title: string, text: string}>}>
     */
    public const array THANK_YOU = [
        'project_inquiry' => ['title' => 'Thank you, we have your enquiry', 'message' => 'A member of our sales team will contact you shortly, in the time you chose.', 'steps' => [
            ['title' => 'We read your enquiry', 'text' => 'A sales advisor reviews the project and the details you shared.'],
            ['title' => 'We call you', 'text' => 'You hear from us at the contact time you chose, usually within one working day.'],
            ['title' => 'You decide at your pace', 'text' => 'Ask for floor plans, a price sheet or a site visit; there is no obligation.'],
        ]],
        'site_visit' => ['title' => 'Your site visit request is in', 'message' => 'We will call you to confirm the date and time of your visit.', 'steps' => [
            ['title' => 'We confirm your slot', 'text' => 'Our team calls to confirm the date and time you asked for.'],
            ['title' => 'You visit the site', 'text' => 'A sales advisor shows you the site, the model unit and the plans.'],
            ['title' => 'Questions answered on the spot', 'text' => 'Bring your family; we can go through payment plans and next steps.'],
        ]],
        'brochure_download' => ['title' => 'Your brochure is on its way', 'message' => 'The download should start at once. Our team may call to answer any questions.', 'steps' => [
            ['title' => 'Your brochure opens', 'text' => 'If the download did not start, use the button on the project page.'],
            ['title' => 'Take your time', 'text' => 'The brochure has plans, specifications and the project timeline.'],
            ['title' => 'Talk to us when ready', 'text' => 'A sales advisor may call to answer any questions.'],
        ]],
        'callback' => ['title' => 'We will call you back', 'message' => 'Our team will call you at the time you chose.', 'steps' => [
            ['title' => 'We note your request', 'text' => 'Your number and preferred time go straight to our sales desk.'],
            ['title' => 'We call you back', 'text' => 'Expect a call at the time you chose, from our hotline number.'],
            ['title' => 'Talk it through', 'text' => 'Ask anything about projects, prices or site visits.'],
        ]],
        'contact' => ['title' => 'Thank you for your message', 'message' => 'We will reply by phone, WhatsApp or email within one working day.', 'steps' => [
            ['title' => 'We read your message', 'text' => 'It goes to the team that handles your subject.'],
            ['title' => 'We reply', 'text' => 'By phone, WhatsApp or email, within one working day.'],
            ['title' => 'We follow up', 'text' => 'If we need more detail we will ask, and keep you posted.'],
        ]],
        'landowner' => ['title' => 'Thank you for your land details', 'message' => 'Our land team will review them and contact you within three working days.', 'steps' => [
            ['title' => 'We review your land details', 'text' => 'Our land team checks the location, size and documents you shared.'],
            ['title' => 'We call you', 'text' => 'Within three working days, to discuss the plot and next steps.'],
            ['title' => 'A site visit, if suitable', 'text' => 'We inspect the land and outline a joint-venture or purchase proposal.'],
        ]],
        'nrb' => ['title' => 'Thank you, we will be in touch', 'message' => 'Our NRB desk will contact you at the time you chose, in Bangladesh time.', 'steps' => [
            ['title' => 'We note your request', 'text' => 'Your details go to our NRB desk.'],
            ['title' => 'We call at your time', 'text' => 'At the hour you chose, in Bangladesh time, by phone or WhatsApp.'],
            ['title' => 'We guide you remotely', 'text' => 'Project selection, bookings and paperwork can all be handled from abroad.'],
        ]],
    ];

    public const string AUTORESPONDER_BODY = "Hello {name},\n\nThank you for contacting Gemcon City Limited. We have received your message and a member of our team will be in touch shortly.\n\nKind regards,\nGemcon City Limited";
}
