<?php

namespace App\Http\Controllers\Site;

use App\Actions\Leads\SubmitLead;
use App\Http\Controllers\Controller;
use App\Support\Forms\FormType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * `POST /forms/{form}` (FR-LEAD workflow): one route for the seven lead
 * forms. The type's Form Request is resolved from the container, which
 * validates it before the Action runs (ARC-001, DEC-32).
 */
class FormController extends Controller
{
    public function store(string $form, SubmitLead $submitLead): RedirectResponse
    {
        $type = FormType::fromSlug($form) ?? abort(404);
        $request = app($type->requestClass());

        $lead = $submitLead->handle($type, $request->validated());

        if ($lead['download_url'] !== null) {
            return Inertia::flash('download', ['url' => $lead['download_url']])->back();
        }

        return redirect()->route('site.thank-you', ['form' => $type->slug()]);
    }
}
