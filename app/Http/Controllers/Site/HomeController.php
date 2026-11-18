<?php

namespace App\Http\Controllers\Site;

use Illuminate\Http\Request;
use Inertia\Response;

/**
 * PG-01: the `home` system page, an ordered block list with the hero first (CMS-PB-001). The shaping
 * is the page controller's; this stays a single-action route (ARC-001).
 */
class HomeController extends PageController
{
    public function __invoke(Request $request): Response
    {
        return $this->home($request);
    }
}
