<?php

namespace App\Support\Leads;

use App\Support\Forms\FormType;
use Closure;
use Illuminate\Http\Request;
use Spatie\Honeypot\SpamResponder\SpamResponder;

/**
 * AC-LEAD-02: a request that filled the honeypot or came back faster than the minimum fill time
 * gets the answer a real submission would get — no 422, so bots learn nothing — but nothing is
 * stored. There is no `lead` flash, so the thank-you page fires no `generate_lead`.
 */
final class FakeSuccessResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next): mixed
    {
        $type = FormType::fromSlug((string) $request->route('form'));

        if ($type !== null && $type !== FormType::BrochureDownload) {
            return redirect()->route('site.thank-you', ['form' => $type->slug()]);
        }

        return back();
    }
}
