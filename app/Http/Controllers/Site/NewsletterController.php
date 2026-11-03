<?php

namespace App\Http\Controllers\Site;

use App\Actions\Newsletter\SubscribeToNewsletter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\NewsletterRequest;
use Illuminate\Http\RedirectResponse;

/**
 * `POST /newsletter` (footer, article): validate, call the Action, go back.
 * The form shows its own inline success state (TOAST-07).
 */
class NewsletterController extends Controller
{
    public function store(NewsletterRequest $request, SubscribeToNewsletter $subscribe): RedirectResponse
    {
        $subscribe->handle($request->validated('email'));

        return back();
    }
}
