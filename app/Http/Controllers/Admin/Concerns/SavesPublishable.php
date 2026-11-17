<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Actions\Publishing\SaveEntityChanges;
use App\Models\Contracts\PublishableEntity;
use App\Support\Auth\SystemRole;
use App\Support\Publishing\StaleEntityException;
use App\Support\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The Save and Autosave of a publishable entity's editor (CMS-PUB-002, CMS-PUB-006, UX-10): one place
 * for the lock check and its answer, so projects, areas and later entities behave the same.
 *
 * A stale save is not an error page: the form stays as typed and the editor opens the conflict dialog
 * (flash `conflict`, "Changed by {user} at {time}", Reload or — for an Admin — Overwrite, TOAST-04).
 */
trait SavesPublishable
{
    /**
     * @param  array<string, mixed>  $payload  validated input
     */
    protected function saveChanges(Request $request, SaveEntityChanges $saveEntityChanges, Model&PublishableEntity $entity, array $payload, string $noun): RedirectResponse
    {
        try {
            $pending = $saveEntityChanges->handle($request->user(), $entity, $payload, $this->loadedLock($request), $this->mayOverwrite($request));
        } catch (StaleEntityException $exception) {
            Inertia::flash('conflict', $exception->details());

            return back();
        }

        if ($pending) {
            Toast::success('Changes saved')->description("Not live yet. Publish the changes when the {$noun} is ready.")->flash();
        } else {
            Toast::success(ucfirst($noun).' saved')->flash();
        }

        return back();
    }

    /**
     * UX-10: the 60-second autosave. Same rules as Save, no toast, answers JSON.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function autosaveChanges(Request $request, SaveEntityChanges $saveEntityChanges, Model&PublishableEntity $entity, array $payload): JsonResponse
    {
        try {
            $pending = $saveEntityChanges->handle($request->user(), $entity, $payload, $this->loadedLock($request), false, true);
        } catch (StaleEntityException $exception) {
            return response()->json(['conflict' => $exception->details()], 409);
        }

        return response()->json([
            'pending' => $pending,
            'lock_version' => (int) $entity->getAttribute('lock_version'),
            'saved_at' => $entity->getAttribute('updated_at')?->toIso8601String(),
        ]);
    }

    /** The `lock_version` the editor loaded, sent beside the form (not part of the validated payload). */
    private function loadedLock(Request $request): ?int
    {
        return $request->filled('lock_version') ? $request->integer('lock_version') : null;
    }

    /** CMS-PUB-006: Overwrite is for Admin and Super Admin only. */
    private function mayOverwrite(Request $request): bool
    {
        return $request->boolean('overwrite')
            && $request->user()->hasAnyRole([SystemRole::Admin->value, SystemRole::SuperAdmin->value]);
    }
}
