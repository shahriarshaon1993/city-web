<?php

namespace App\Http\Controllers\Site;

use App\Actions\Newsletter\ConfirmNewsletterSubscription;
use App\Actions\Newsletter\SubscribeToNewsletter;
use App\Actions\Newsletter\UnsubscribeFromNewsletter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\NewsletterRequest;
use App\Support\Leads\LeadContext;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * `POST /newsletter` (footer, article): validate, call the Action, go back. The form shows its own
 * inline success state (TOAST-07). `GET /newsletter/confirm|unsubscribe/{token}` are the links in
 * the mails; they answer with a small status page, `noindex,nofollow`.
 */
class NewsletterController extends Controller
{
    /** Copy of the status page: spec/leads.md "Newsletter" has none, so it lives here with the logic. */
    private const array STATUS_COPY = [
        'confirmed' => ['title' => 'Subscription confirmed', 'message' => 'Thank you. You will now receive project updates from us.'],
        'unsubscribed' => ['title' => 'You are unsubscribed', 'message' => 'We will not send you project updates any more.'],
        'invalid' => ['title' => 'This link is not valid', 'message' => 'It may be mistyped or no longer in use. You can sign up again from the footer of any page.'],
    ];

    public function store(NewsletterRequest $request, SubscribeToNewsletter $subscribe): RedirectResponse
    {
        $subscribe->handle(
            $request->validated('email'),
            $request->validated('source_url'),
            LeadContext::fromRequest($request),
        );

        return back();
    }

    public function confirm(Request $request, string $token, ConfirmNewsletterSubscription $confirm): Response
    {
        return $this->status($request, $confirm->handle($token) ? 'confirmed' : 'invalid');
    }

    public function unsubscribe(Request $request, string $token, UnsubscribeFromNewsletter $unsubscribe): Response
    {
        return $this->status($request, $unsubscribe->handle($token) ? 'unsubscribed' : 'invalid');
    }

    private function status(Request $request, string $state): Response
    {
        $copy = self::STATUS_COPY[$state];

        return Inertia::render('Site/NewsletterStatus', [
            'state' => $state,
            'title' => $copy['title'],
            'message' => $copy['message'],
            'seo' => SeoData::page($copy['title'])->noindex(follow: false)->toArray($request),
        ]);
    }
}
