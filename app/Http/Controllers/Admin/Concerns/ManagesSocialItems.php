<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Actions\RecycleBin\MoveManyToRecycleBin;
use App\Actions\RecycleBin\MoveToRecycleBin;
use App\Actions\Social\ReorderSocialItems;
use App\Actions\Social\SetSocialPublished;
use App\Http\Requests\Admin\BulkSocialRequest;
use App\Http\Requests\Admin\ReorderRequest;
use App\Queries\Admin\AdminTable;
use App\Support\RecycleBin\Trashable;
use App\Support\Toasts\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * What the lists of testimonials, partners, FAQs, team members and offices share (ADM-SCR-09): the
 * inline Published switch, drag order with Undo, Delete to the Recycle bin (DEL-02) and the bulk bar.
 * A controller names its entity; the Actions do the work (DEC-32).
 */
trait ManagesSocialItems
{
    /** @return class-string<Model&Trashable> */
    abstract protected function itemClass(): string;

    /**
     * Singular and plural nouns for toasts: ['testimonial', 'testimonials'].
     *
     * @return array{0: string, 1: string}
     */
    abstract protected function nouns(): array;

    /** The activity-log name of the entity. */
    abstract protected function logName(): string;

    protected function labelOf(Model&Trashable $item): string
    {
        return $item->binLabel();
    }

    protected function respondPublished(Request $request, Model&Trashable $item, SetSocialPublished $setPublished): RedirectResponse
    {
        Gate::authorize('update', $item);

        $published = $request->boolean('published');
        $setPublished->handle($request->user(), $item, $published, $this->logName(), $this->labelOf($item));

        Toast::success(ucfirst($this->nouns()[0]).($published ? ' published' : ' unpublished'))
            ->description($this->labelOf($item))
            ->flash();

        return back();
    }

    protected function respondOrder(ReorderRequest $request, ReorderSocialItems $reorder, string $orderRoute): RedirectResponse
    {
        abort_unless($request->user()->can('social.manage'), 403);

        $previous = $reorder->handle($request->user(), $this->itemClass(), $request->ids(), $this->logName());

        Toast::success('Order saved')->action('Undo', route($orderRoute), 'patch', ['ids' => $previous])->flash();

        return back();
    }

    /** DEL-02: Delete = Recycle bin, with Undo (TOAST-02). */
    protected function respondDestroy(Request $request, Model&Trashable $item, MoveToRecycleBin $moveToRecycleBin): RedirectResponse
    {
        Gate::authorize('delete', $item);

        try {
            $entry = $moveToRecycleBin->handle($request->user(), $item);
        } catch (ValidationException $exception) {
            Toast::error(ucfirst($this->nouns()[0]).' not deleted')->description($exception->validator->errors()->first())->flash();

            return back();
        }

        Toast::success(ucfirst($this->nouns()[0]).' moved to Recycle bin')
            ->description($this->labelOf($item))
            ->undo(route('admin.recycle-bin.restore', $entry), ['undo' => true])
            ->flash();

        return back();
    }

    /**
     * @template TItem of Model&Trashable
     *
     * @param  AdminTable<TItem>  $table
     */
    protected function respondBulk(BulkSocialRequest $request, AdminTable $table, MoveManyToRecycleBin $moveMany, SetSocialPublished $setPublished): RedirectResponse
    {
        [$one, $many] = $this->nouns();
        $actor = $request->user();
        $items = $table->selection($request, $actor)->limit(BulkSocialRequest::MAX_IDS * 20)->get();
        $action = $request->string('action')->toString();

        if ($action === 'delete') {
            $result = $moveMany->handle($actor, $items);
            $count = count($result['entries']);
            $title = $count === 0 ? "No {$many} were deleted" : $count.' '.($count === 1 ? $one : $many).' moved to Recycle bin';

            $toast = $result['refused'] === []
                ? Toast::success($title)
                : Toast::warning($title.' · '.count($result['refused']).' skipped')->description(implode(' ', array_slice($result['refused'], 0, 3)));

            if ($count > 0) {
                $toast->undo(route('admin.recycle-bin.restore-many'), ['ids' => array_map(fn ($entry): int => $entry->id, $result['entries']), 'undo' => true]);
            }

            $toast->flash();

            return back();
        }

        $published = $action === 'publish';
        $changed = 0;

        foreach ($items as $item) {
            if ((bool) $item->getAttribute('is_published') !== $published) {
                $setPublished->handle($actor, $item, $published, $this->logName(), $this->labelOf($item));
                $changed++;
            }
        }

        Toast::success($changed === 0 ? "No {$many} were changed" : $changed.' '.($changed === 1 ? $one : $many).($published ? ' published' : ' unpublished'))->flash();

        return back();
    }
}
