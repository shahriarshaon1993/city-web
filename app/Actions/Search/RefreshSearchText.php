<?php

namespace App\Actions\Search;

use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Support\Search\SearchText;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-SRCH-010: rewrites `search_text` of one project, post or page. A plain query-builder update, so it
 * fires no model events (and cannot loop back into the model's own `saved` hook).
 */
final class RefreshSearchText
{
    public function handle(Model $record): void
    {
        if (! SearchText::supports($record) || ! $record->exists) {
            return;
        }

        $text = SearchText::for($record);

        $record->newQuery()->withoutGlobalScopes()->whereKey($record->getKey())->toBase()->update(['search_text' => $text]);
        $record->setAttribute('search_text', $text)->syncOriginalAttribute('search_text');
    }

    /**
     * Every project, post and page, including the ones in the Recycle bin (a restore must find them).
     */
    public function handleAll(): int
    {
        $count = 0;

        foreach ([Project::class, Post::class, Page::class] as $class) {
            $class::withTrashed()->each(function (Model $record) use (&$count): void {
                $this->handle($record);
                $count++;
            });
        }

        return $count;
    }
}
