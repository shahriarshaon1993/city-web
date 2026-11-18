<?php

namespace App\Http\Resources\Site;

use App\Models\Post;
use App\Queries\Site\ProjectCards;
use App\Support\News\NewsCopy;
use App\Support\News\PostBody;
use Illuminate\Http\Request;

/**
 * PG-14: the article (types/news.ts `NewsArticle`) — the card plus the rendered body, author, tags and
 * the related projects (published ones only; hidden prices never leave the server, AC-PROJ-01, because
 * the cards come from `ProjectCards`).
 *
 * @mixin Post
 */
class PostResource extends NewsCardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // `byIds` leaves unpublished projects out.
        $projectIds = array_values($this->projects->pluck('id')->map(intval(...))->all());

        return [
            ...parent::toArray($request),
            'body' => PostBody::render($this->body, NewsCopy::labels()['watch_video']),
            'author' => ['name' => $this->author_user_id === null ? NewsCopy::DEFAULT_AUTHOR : $this->author->name, 'role' => ''],
            'tags' => $this->tags->map(fn ($tag): array => ['name' => $tag->name, 'slug' => $tag->slug, 'url' => $tag->path()])->values()->all(),
            'projects' => $projectIds === [] ? [] : ProjectCards::byIds($projectIds),
        ];
    }
}
