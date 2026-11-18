<?php

namespace App\Actions\Pages;

use App\Actions\Activity\LogActivity;
use App\Models\Page;
use App\Models\User;
use App\Queries\Admin\PageEditor;
use Illuminate\Support\Facades\DB;

/**
 * CRUD-05: Duplicate page = a Draft "Copy of {title}" with a free slug (`-copy`, `-copy-2`…) that keeps
 * the blocks (with their media references) and the SEO panel. Revisions, slug history and the
 * published state are not copied. Only custom builder pages can be duplicated.
 */
final class DuplicatePage
{
    public function __construct(
        private readonly SavePage $savePage,
        private readonly LogActivity $logActivity,
    ) {}

    public function handle(User $actor, Page $source): Page
    {
        return DB::transaction(function () use ($actor, $source): Page {
            $form = PageEditor::form($source);
            $form['title'] = $this->freeTitle($source->title);
            $form['slug'] = $this->freeSlug($source->slug);
            $form['template'] = Page::BUILDER;
            $form['blocks'] = array_map(fn (array $block): array => [...$block, 'id' => null], $form['blocks']);

            $copy = $this->savePage->handle($actor, $form, null, false);

            $this->logActivity->handle('pages', 'created', "Page duplicated: {$copy->title}", $actor, $copy, ['label' => $copy->title, 'source' => $source->title]);

            return $copy;
        });
    }

    private function freeTitle(string $title): string
    {
        return mb_substr("Copy of {$title}", 0, 120);
    }

    /** Free among all pages, the bin included (DEL-08). */
    private function freeSlug(string $slug): string
    {
        $base = mb_substr($slug, 0, 80 - 9);
        $candidate = "{$base}-copy";

        for ($number = 2; Page::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-copy-{$number}";
        }

        return $candidate;
    }
}
