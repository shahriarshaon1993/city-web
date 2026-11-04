<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Roles\DeleteRole;
use App\Actions\Roles\SaveRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveRoleRequest;
use App\Queries\Admin\RolesTable;
use App\Support\Auth\PermissionMatrix;
use App\Support\Auth\SystemRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * ADM-SCR-16 roles tab: a list (DataTable) and a full-page editor with permission checkboxes.
 */
class RoleController extends Controller
{
    public function index(Request $request, RolesTable $table): Response|RedirectResponse
    {
        Gate::authorize('viewAny', Role::class);

        $actor = $request->user();

        if (($url = $table->viewRedirect($request, $actor)) !== null) {
            return redirect($url);
        }

        return Inertia::render('admin/users/Roles', [
            ...$table->props($request, $actor),
            'canManageUsers' => $actor->can('users.manage'),
            'canShareViews' => $actor->can('views.share'),
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Users & roles', 'href' => route('admin.users.index')],
                ['title' => 'Roles', 'href' => null],
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Role::class);

        return $this->form(null);
    }

    public function edit(Role $role): Response
    {
        Gate::authorize('viewAny', Role::class);

        return $this->form($role);
    }

    public function store(SaveRoleRequest $request, SaveRole $saveRole): RedirectResponse
    {
        $role = $saveRole->handle($request->user(), null, $request->string('name')->toString(), $request->input('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role “:name” created.', ['name' => $role->name])]);

        return to_route('admin.roles.index');
    }

    public function update(SaveRoleRequest $request, Role $role, SaveRole $saveRole): RedirectResponse
    {
        $role = $saveRole->handle($request->user(), $role, $request->string('name')->toString(), $request->input('permissions', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role “:name” saved.', ['name' => $role->name])]);

        return to_route('admin.roles.index');
    }

    public function destroy(Request $request, Role $role, DeleteRole $deleteRole): RedirectResponse
    {
        Gate::authorize('delete', $role);

        try {
            $deleteRole->handle($request->user(), $role);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->validator->errors()->first()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role “:name” deleted.', ['name' => $role->name])]);

        return to_route('admin.roles.index');
    }

    private function form(?Role $role): Response
    {
        return Inertia::render('admin/users/RoleForm', [
            'role' => $role === null ? null : [
                'id' => $role->id,
                'name' => $role->name,
                'system' => in_array($role->name, SystemRole::names(), true),
                'users_count' => $role->users()->count(),
            ],
            'groups' => PermissionMatrix::labelled(),
            'selected' => $role === null ? [SaveRole::ALWAYS] : $role->permissions->pluck('name')->values()->all(),
            'required' => SaveRole::ALWAYS,
            'breadcrumbs' => [
                ['title' => 'System', 'href' => null],
                ['title' => 'Users & roles', 'href' => route('admin.users.index')],
                ['title' => 'Roles', 'href' => route('admin.roles.index')],
                ['title' => $role === null ? 'New role' : $role->name, 'href' => null],
            ],
        ]);
    }
}
