<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Privacy\ErasePersonalData;
use App\Actions\Privacy\ExportPersonalData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ErasePersonalDataRequest;
use App\Http\Requests\Admin\PersonalDataRequest;
use App\Queries\Admin\PersonalDataLookup;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * NFR-PRIV-03 Personal data: find everything held for a phone and/or an email, export it, or erase it.
 * The person's phone and email travel in POST bodies, never in a URL (access logs, history).
 */
class PrivacyController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->page(null, null);
    }

    public function search(PersonalDataRequest $request, PersonalDataLookup $lookup): Response
    {
        $term = $request->term();

        return $this->page($term->toArray(), $lookup->summary($term));
    }

    public function export(PersonalDataRequest $request, ExportPersonalData $export): JsonResponse
    {
        return response()->json($export->handle($request->user(), $request->term()));
    }

    public function erase(ErasePersonalDataRequest $request, ErasePersonalData $erase): RedirectResponse
    {
        $counts = $erase->handle($request->user(), $request->term());

        Toast::success('Personal data erased')
            ->description(implode(', ', array_filter([
                $this->count($counts['leads'], 'lead'),
                $this->count($counts['applications'], 'application'),
                $this->count($counts['subscriptions'], 'subscription'),
            ])) ?: 'Nothing was held for them')
            ->flash();

        return redirect()->route('admin.privacy.index');
    }

    /**
     * @param  array{phone: string|null, email: string|null}|null  $searched
     * @param  array<string, mixed>|null  $results
     */
    private function page(?array $searched, ?array $results): Response
    {
        return Inertia::render('admin/privacy/Index', [
            'searched' => $searched,
            'results' => $results,
            'confirmWord' => ErasePersonalDataRequest::CONFIRM_WORD,
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Personal data', 'href' => null],
            ],
        ]);
    }

    private function count(int $number, string $noun): ?string
    {
        return $number === 0 ? null : $number.' '.$noun.($number === 1 ? '' : 's');
    }
}
