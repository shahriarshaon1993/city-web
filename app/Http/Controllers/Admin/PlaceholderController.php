<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Admin\AdminNavigation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One page for every sidebar item whose screen is built in a later phase.
 * The route default `item` is the NavItem key; the permission middleware on
 * the route already ran.
 */
class PlaceholderController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $item = AdminNavigation::find((string) $request->route('item'));

        abort_if($item === null, 404);

        return Inertia::render('admin/Placeholder', [
            'title' => $item->label,
            'description' => $item->description,
            'icon' => $item->icon,
            'phase' => $item->phase,
            'breadcrumbs' => [
                ['title' => AdminNavigation::groupOf($item), 'href' => null],
                ['title' => $item->label, 'href' => null],
            ],
        ]);
    }
}
