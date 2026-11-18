<?php

namespace App\Http\Resources\Admin;

use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the categories list. `posts_count` and the first few posts feed the delete dialog
 * ("Move posts to…", DEL-03).
 *
 * @mixin PostCategory
 */
class PostCategoryRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->user();
        $count = (int) $this->getAttribute('posts_count');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'path' => $this->path(),
            'description' => $this->description,
            'posts_count' => $count,
            'posts' => $count === 0 ? [] : $this->posts()->orderByDesc('updated_at')->limit(8)->get(['id', 'title'])
                ->map(fn ($post): array => ['label' => $post->title, 'href' => route('admin.news.edit', $post->id)])->all(),
            'can' => [
                'edit' => $actor->can('update', $this->resource),
                'delete' => $actor->can('delete', $this->resource),
            ],
        ];
    }
}
