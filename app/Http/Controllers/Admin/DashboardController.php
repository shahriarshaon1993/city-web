<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin home. The widgets of ADM-DASH arrive in 18.1; until then the page
 * lists the sections the signed-in role may open (from the shared navigation).
 */
class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'breadcrumbs' => [['title' => 'Dashboard', 'href' => null]],
        ]);
    }
}
