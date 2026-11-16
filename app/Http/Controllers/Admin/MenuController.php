<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Menus\SaveMenu;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveMenuRequest;
use App\Models\Menu;
use App\Queries\Admin\MenuEditor;
use App\Support\Menus\LinkTargets;
use App\Support\Menus\LinkType;
use App\Support\Menus\MenuKey;
use App\Support\Toasts\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ADM-SCR-12 Menus: one tab per menu (CMS-NAV-001) with the tree editor. A save sends the whole
 * tree; the Action stores it and raises the menu cache version.
 */
class MenuController extends Controller
{
    public function index(Request $request, ?Menu $menu = null): Response|RedirectResponse
    {
        if ($menu === null) {
            return to_route('admin.menus.index', ['menu' => MenuKey::Header->value]);
        }

        Gate::authorize('view', $menu);

        return Inertia::render('admin/Menus', [
            'tabs' => MenuEditor::tabs(),
            'menu' => MenuEditor::menu($menu),
            'options' => MenuEditor::options(),
            'canManage' => $request->user()->can('update', $menu),
            'lastSaved' => $menu->updated_at?->toIso8601String(),
            'breadcrumbs' => [
                ['title' => 'Content', 'href' => null],
                ['title' => 'Menus', 'href' => null],
            ],
        ]);
    }

    public function update(SaveMenuRequest $request, Menu $menu, SaveMenu $saveMenu): RedirectResponse
    {
        $result = $saveMenu->handle(
            $request->user(),
            $menu,
            trim($request->validated('name')),
            $request->validated('items'),
        );

        if (! SaveMenu::changed($result)) {
            Toast::info('No changes to save')->flash();

            return back();
        }

        Toast::success("{$menu->fresh()->name} menu saved")
            ->description($this->summary($result))
            ->flash();

        return back();
    }

    /**
     * The picker's search: up to 25 targets of one type (CMS-NAV-002), narrowed by the typed text.
     */
    public function targets(Request $request): JsonResponse
    {
        abort_unless($request->user()->canAny(['menus.view', 'menus.manage']), 403);

        $type = LinkType::tryFrom((string) $request->query('type'));

        abort_if($type === null || ! $type->isEntity(), 404);

        return response()->json([
            'targets' => array_map(
                fn ($target): array => $target->toArray(),
                LinkTargets::search($type, (string) $request->query('q', '')),
            ),
        ]);
    }

    /**
     * @param  array{added: int, updated: int, removed: int, renamed: bool}  $result
     */
    private function summary(array $result): string
    {
        $parts = [];

        foreach (['added' => 'added', 'updated' => 'changed', 'removed' => 'removed'] as $key => $verb) {
            if ($result[$key] > 0) {
                $parts[] = $result[$key].' '.($result[$key] === 1 ? 'item' : 'items').' '.$verb;
            }
        }

        if ($result['renamed']) {
            $parts[] = 'renamed';
        }

        return ucfirst(implode(' · ', $parts));
    }
}
