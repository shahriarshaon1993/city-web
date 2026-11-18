<?php

namespace App\Support\Publishing;

use App\Actions\Posts\SavePost;
use App\Models\Post;
use App\Models\User;
use App\Queries\Admin\PostEditor;
use App\Support\News\PostChecklist;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Posts in the publishing workflow (FR-NEWS, CMS-PUB): the editor payload is `PostEditor::form`,
 * applying it is `SavePost`, the publish rules are `PostChecklist`. A scheduled post is a 404 until its time
 * (AC-NEWS-01).
 */
final class PostPublishing implements PublishingDriver
{
    public function __construct(
        private readonly PostHydrator $hydrator,
        private readonly SavePost $savePost,
        private readonly PostChecklist $checklist,
    ) {}

    public function type(): string
    {
        return 'post';
    }

    public function logName(): string
    {
        return 'posts';
    }

    public function noun(): string
    {
        return 'post';
    }

    public function label(Model $entity): string
    {
        return $this->post($entity)->title;
    }

    public function publicPath(Model $entity): string
    {
        return $this->post($entity)->path();
    }

    public function snapshot(Model $entity): array
    {
        return PostEditor::form($this->post($entity));
    }

    public function hydrate(Model $entity, array $payload, bool $preview = false): Post
    {
        return $this->hydrator->handle($this->post($entity), $payload);
    }

    public function apply(?User $actor, Model $entity, array $payload, bool $log = true): Post
    {
        return $this->savePost->handle($actor, $payload, $this->post($entity), $log);
    }

    public function checklist(Model $entity): array
    {
        return $this->checklist->items($this->post($entity));
    }

    public function fieldLabels(): array
    {
        return [
            'title' => 'Title', 'slug' => 'URL', 'excerpt' => 'Summary', 'body' => 'Article', 'cover_asset_id' => 'Cover image',
            'category_id' => 'Category', 'author_user_id' => 'Author', 'is_featured' => 'Featured', 'tags' => 'Tags',
            'project_ids' => 'Related projects', 'seo' => 'SEO',
        ];
    }

    public function htmlFields(): array
    {
        return ['body'];
    }

    private function post(Model $entity): Post
    {
        return $entity instanceof Post ? $entity : throw new InvalidArgumentException('Expected a post.');
    }
}
