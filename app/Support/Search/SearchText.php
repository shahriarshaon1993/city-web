<?php

namespace App\Support\Search;

use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Support\News\PostBody;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-SRCH-010: the plain text stored in `search_text` beside each record's title. Projects: tagline, area
 * and city names, segment, overview. Posts: excerpt and body. Pages: meta description.
 */
final class SearchText
{
    public static function for(Model $record): string
    {
        $parts = match (true) {
            $record instanceof Project => [
                $record->tagline,
                $record->location->name,
                $record->location->parent?->name,
                $record->segment->label(),
                PostBody::plainText($record->overview),
            ],
            $record instanceof Post => [$record->excerpt, PostBody::plainText($record->body)],
            $record instanceof Page => [$record->seoMeta()->value('meta_description')],
            default => [],
        };

        return trim(implode(' ', array_filter($parts, fn (?string $part): bool => filled($part))));
    }

    public static function supports(Model $record): bool
    {
        return $record instanceof Project || $record instanceof Post || $record instanceof Page;
    }
}
