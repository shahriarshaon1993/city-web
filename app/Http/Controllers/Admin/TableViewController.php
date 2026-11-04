<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tables\DeleteTableView;
use App\Actions\Tables\SaveTableView;
use App\Actions\Tables\UpdateTableView;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTableViewRequest;
use App\Http\Requests\Admin\UpdateTableViewRequest;
use App\Models\SavedTableView;
use App\Queries\Admin\AdminTables;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * ADM-TBL-010 saved views. After a save the browser lands on the table with `?view={id}`.
 */
class TableViewController extends Controller
{
    public function store(SaveTableViewRequest $request, SaveTableView $saveTableView): RedirectResponse
    {
        $view = $saveTableView->handle(
            $request->user(),
            $request->string('table_key')->toString(),
            $request->string('name')->toString(),
            $request->input('state', []),
            $request->boolean('is_shared'),
            $request->boolean('is_default'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('View “:name” saved.', ['name' => $view->name])]);

        return $this->open($view);
    }

    public function update(UpdateTableViewRequest $request, SavedTableView $view, UpdateTableView $updateTableView): RedirectResponse
    {
        $state = $request->has('state') ? $request->input('state') : null;
        $default = $request->has('is_default') ? $request->boolean('is_default') : null;

        $view = $updateTableView->handle($view, $state, $default);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $state !== null
                ? __('View “:name” updated.', ['name' => $view->name])
                : ($view->is_default ? __('“:name” is now your default view.', ['name' => $view->name]) : __('“:name” is no longer your default view.', ['name' => $view->name])),
        ]);

        return $state !== null ? $this->open($view) : back();
    }

    public function destroy(Request $request, SavedTableView $view, DeleteTableView $deleteTableView): RedirectResponse
    {
        Gate::authorize('delete', $view);

        $deleteTableView->handle($view);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('View “:name” deleted.', ['name' => $view->name])]);

        return redirect(AdminTables::find($view->table_key)?->url() ?? route('admin.dashboard'));
    }

    private function open(SavedTableView $view): RedirectResponse
    {
        $url = AdminTables::find($view->table_key)?->url() ?? route('admin.dashboard');

        return redirect($url.'?'.http_build_query([...$view->state, 'view' => $view->id]));
    }
}
