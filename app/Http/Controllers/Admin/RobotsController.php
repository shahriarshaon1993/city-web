<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Seo\UpdateRobotsTxt;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRobotsRequest;
use App\Models\Setting;
use App\Support\Redirects\RobotsTxt;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-14 Robots.txt tab (SEO-TEC-010): the editor with its live preview. `seo.view` reads it,
 * `seo.manage` saves it.
 */
class RobotsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('admin/Robots', [
            'body' => RobotsTxt::body(),
            'defaultBody' => RobotsTxt::defaultBody(),
            'isCustom' => RobotsTxt::isCustom(),
            'served' => RobotsTxt::served(),
            'blocksEverything' => RobotsTxt::blocksEverything(),
            'environment' => app()->environment(),
            'publicUrl' => url('robots.txt'),
            'canManage' => $request->user()->can('seo.manage'),
            'lastSaved' => Setting::query()->where('group', 'seo')->where('key', 'robots_body')->value('updated_at'),
            'breadcrumbs' => [
                ['title' => 'SEO', 'href' => null],
                ['title' => 'Robots.txt', 'href' => null],
            ],
        ]);
    }

    public function update(UpdateRobotsRequest $request, UpdateRobotsTxt $updateRobotsTxt): RedirectResponse
    {
        $body = $request->validated('body');
        $changed = $updateRobotsTxt->handle($request->user(), $body);

        if (! $changed) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success(trim((string) $body) === '' ? 'robots.txt reset to the default' : 'robots.txt saved')->flash();

        return back();
    }
}
