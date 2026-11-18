<?php

namespace App\Actions\News;

use App\Actions\Activity\LogActivity;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * FR-NEWS-002: creates or edits a news category from the dialog (ADM-SCR-08). A new category goes to the
 * end of the order; the slug is the public address `/news/category/{slug}` (a change is remembered
 * as slug history and answers 301).
 */
final class SavePostCategory
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  array<string, mixed>  $data  validated `name`, `slug`, `description`
     * @return array{category: PostCategory, changed: bool}
     */
    public function handle(User $actor, array $data, ?PostCategory $category = null): array
    {
        $creating = $category === null;
        $category ??= new PostCategory(['sort_order' => ((int) PostCategory::withTrashed()->max('sort_order')) + 1]);

        $description = trim((string) ($data['description'] ?? ''));

        $category->fill([
            'name' => trim((string) $data['name']),
            'slug' => ($data['slug'] ?? '') !== '' ? $data['slug'] : Str::slug((string) $data['name']),
            'description' => $description === '' ? null : $description,
        ]);

        $changed = $creating || $category->isDirty();

        if ($changed) {
            $category->save();

            $this->logActivity->handle(
                'news',
                $creating ? 'created' : 'updated',
                ($creating ? 'News category created: ' : 'News category updated: ').$category->name,
                $actor,
                $category,
                ['label' => $category->name],
            );
        }

        return ['category' => $category, 'changed' => $changed];
    }
}
