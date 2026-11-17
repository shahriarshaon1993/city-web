<?php

namespace App\Support\Settings\Definitions;

use App\Support\Settings\Field;
use App\Support\Settings\SettingsGroup;

/**
 * Settings → Forms and Notifications (spec/cms.md §8.2). Forms = consent and thank-you copy per
 * lead form, spam-protection keys and the brochure gate. Notifications = who is emailed and the
 * auto-reply (FR-LEAD-008).
 */
final class FormGroups
{
    /**
     * @return list<SettingsGroup>
     */
    public static function all(): array
    {
        return [self::forms(), self::notifications()];
    }

    private static function forms(): SettingsGroup
    {
        $consent = Field::object('consent', 'Per form', array_map(
            fn (string $form, string $label): Field => Field::textarea($form, $label)->required()->max(500)->rows(3)->default(FormCopy::CONSENT[$form]),
            array_keys(FormCopy::FORMS),
            array_values(FormCopy::FORMS),
        ));

        $thankYouForms = [];

        foreach (FormCopy::THANK_YOU as $form => $copy) {
            $thankYouForms[] = Field::object($form, FormCopy::FORMS[$form], [
                Field::text('title', 'Heading')->required()->max(80)->default($copy['title']),
                Field::textarea('message', 'Message')->required()->max(300)->rows(2)->default($copy['message']),
                Field::list('steps', 'What happens next')->ofObject([
                    Field::text('title', 'Step')->required()->max(60),
                    Field::text('text', 'Detail')->required()->max(160),
                ])->minItems(1)->maxItems(4)->default($copy['steps'])
                    ->help('Up to 4 short steps, shown as a numbered list.'),
            ]);
        }

        return new SettingsGroup('forms', 'Forms', 'The wording on the enquiry forms, thank-you pages and spam protection.', 'Visitors', SettingsGroup::MANAGE, [
            'Consent text' => [$consent],
            'Thank-you pages' => [
                Field::object('thank_you', 'Per form', $thankYouForms),
                Field::text('thank_you_related_heading', 'Heading above the suggested projects')->required()->max(60)->default('You may also like'),
                Field::text('thank_you_whatsapp', 'WhatsApp message')->required()->max(200)->default('Hi, I just contacted Gemcon City through the website.')
                    ->help('Written into the chat when the visitor taps WhatsApp on a thank-you page.'),
                Field::text('thank_you_whatsapp_project', 'WhatsApp message about a project')->required()->max(200)->tokens(['project'])
                    ->default('Hi, I just contacted Gemcon City through the website about {project}.')
                    ->help('Used when the enquiry named a project. {project} is replaced by its name.'),
            ],
            'Dialogs and links' => [
                Field::link('privacy_url', 'Privacy policy link')->required()->default('/privacy-policy')->placeholder('/privacy-policy')
                    ->help('The "privacy policy" link under every consent checkbox.'),
                Field::object('callback_dialog', 'Call-back dialog', [
                    Field::text('title', 'Heading')->required()->max(60)->default('Request a call back'),
                    Field::textarea('text', 'Text')->required()->max(200)->rows(2)->default('Leave your number and a time that suits you. Our team will call you.'),
                ]),
            ],
            'Spam protection and brochures' => [
                Field::text('turnstile_site_key', 'Turnstile site key')->max(100)->mono()
                    ->help('From the Cloudflare dashboard (Turnstile). Public; it is sent to the browser. Leave empty to skip the check.'),
                Field::secret('turnstile_secret', 'Turnstile secret key')->max(100)
                    ->help('Stays on the server and is never shown again after saving.'),
                Field::int('honeypot_min_seconds', 'Fastest believable submit (seconds)')->required()->min(1)->max(30)->default(3)
                    ->help('A form sent faster than this is treated as spam. The default of 3 seconds suits most forms.'),
                Field::bool('brochure_gating_default', 'Ask for name and phone before a brochure download')->default(true)
                    ->help('The default for new projects; each project can override it.'),
            ],
        ], [
            'Consent text' => 'The sentence next to each consent checkbox. It is saved with every lead as proof of what the visitor agreed to.',
            'Thank-you pages' => 'What the visitor reads after sending each form.',
        ]);
    }

    private static function notifications(): SettingsGroup
    {
        $recipients = array_map(
            fn (string $form, string $label): Field => Field::list($form, $label)->of(Field::email('email', 'Email address'))->maxItems(5),
            array_slice(array_keys(FormCopy::FORMS), 0, 7),
            array_slice(array_values(FormCopy::FORMS), 0, 7),
        );

        return new SettingsGroup('notifications', 'Notifications', 'Who is emailed when a lead arrives, and the automatic reply to the visitor.', 'Visitors', SettingsGroup::INTEGRATIONS, [
            'Email alerts' => [
                Field::bool('email_enabled', 'Email the team about new leads')
                    ->help('Off by default until the mail server is set up and tested.'),
                ...$recipients,
            ],
            'Automatic reply' => [
                Field::bool('autoresponder_enabled', 'Send an automatic reply to the visitor')
                    ->help('Off by default. Sent to visitors who gave an email address.'),
                Field::text('autoresponder_subject', 'Subject')->required()->max(120)->tokens(['name', 'project'])->default('Thank you for contacting Gemcon City'),
                Field::textarea('autoresponder_body', 'Message')->required()->max(2000)->rows(8)->tokens(['name', 'project'])->default(FormCopy::AUTORESPONDER_BODY)
                    ->help('{name} is replaced by the visitor\'s name, {project} by the project they asked about.'),
            ],
        ], [
            'Email alerts' => 'Leave a form without recipients to notify nobody for it. Up to 5 addresses per form.',
        ]);
    }
}
