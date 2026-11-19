<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Dashboard\DismissGettingStarted;
use App\Http\Controllers\Controller;
use App\Queries\Admin\DashboardData;
use App\Support\Admin\GettingStarted;
use App\Support\Toasts\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin home (ADM-DASH, design/admin.md §12.2). Every widget is built only when the user holds its
 * permission; the three chart widgets take their own range (7 / 30 / 90 days) so one can change
 * without reloading the others.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $data = new DashboardData($request->user());
        $leads = $data->seesLeads();

        $trendDays = DashboardData::range($request->query('trend'), 90);
        $projectDays = DashboardData::range($request->query('projects'), 30);
        $sourceDays = DashboardData::range($request->query('sources'), 30);

        return Inertia::render('admin/Dashboard', [
            'breadcrumbs' => [['title' => 'Dashboard', 'href' => null]],
            'ranges' => ['trend' => $trendDays, 'projects' => $projectDays, 'sources' => $sourceDays],
            'kpis' => $data->kpis(),
            'quickActions' => $data->quickActions(),
            'trend' => $leads ? $data->trend($trendDays) : null,
            'pipeline' => $leads ? $data->pipeline() : null,
            'leadsByProject' => $leads ? $data->leadsByProject($projectDays) : null,
            'leadSources' => $leads ? $data->leadSources($sourceDays) : null,
            'unreadEnquiries' => $leads ? $data->unreadEnquiries() : null,
            'followUps' => $data->sees('leads.update') && $leads ? $data->followUps() : null,
            'recentActivity' => $data->sees('activity.view-all') ? $data->recentActivity() : null,
            'seoHealth' => $data->sees('seo.view', 'seo.manage', 'redirects.manage') ? $data->seoHealth() : null,
            'gettingStarted' => $data->sees('settings.manage') && GettingStarted::visible()
                ? ['steps' => GettingStarted::steps()]
                : null,
        ]);
    }

    /** HELP-05: the checklist can be put away; it hides itself once every step is done. */
    public function dismissGettingStarted(Request $request, DismissGettingStarted $dismiss): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $dismiss->handle($request->user());

        Toast::success('Checklist hidden')->description('Every screen is still in the sidebar.')->flash();

        return back();
    }
}
