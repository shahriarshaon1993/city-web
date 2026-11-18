<?php

namespace App\Http\Resources\Site;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CMP-NEWS-01: a post as a card (resources/js/types/news.ts `NewsCard`). Expects `category` and
 * `cover.media` to be loaded. Read time is part of the shape; the page decides whether to show it
 * (PG-14 only, FR-NEWS-004).
 *
 * @mixin Post
 */
class NewsCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'url' => $this->path(),
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'cover' => $this->cover === null ? null : (new MediaImageResource($this->cover))->resolve($request),
            'category' => ['name' => $this->category->name, 'slug' => $this->category->slug, 'url' => $this->category->path()],
            'published_at' => ($this->published_at ?? $this->created_at)?->timezone('Asia/Dhaka')->toDateString(),
            'read_minutes' => $this->read_minutes,
        ];
    }
}
